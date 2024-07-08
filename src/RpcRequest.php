<?php

namespace Ufo\RpcObject;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Throwable;
use TypeError;
use Ufo\RpcError\AbstractRpcErrorException;
use Ufo\RpcError\IProcedureExceptionInterface;
use Ufo\RpcError\IServerExceptionInterface;
use Ufo\RpcError\IUserInputExceptionInterface;
use Ufo\RpcError\RpcAsyncRequestException;
use Ufo\RpcError\RpcBadRequestException;
use Ufo\RpcError\RpcJsonParseException;
use Ufo\RpcError\RpcRuntimeException;
use Ufo\RpcObject\Rules\ParamsSplitter;
use Ufo\RpcObject\Rules\RequestRules;
use Ufo\RpcObject\Rules\Validator\Validator;
use Ufo\RpcObject\Transformer\Transformer;

class RpcRequest
{
    const S_GROUP = 'raw';
    const DEFAULT_VERSION = '2.0';

    #[Ignore]
    protected ?Throwable $error = null;

    /**
     * @var RpcRequestRequire[]
     */
    #[Ignore]
    protected array $require = [];
    #[Ignore]
    protected array $requireIds = [];
    #[Ignore]
    protected ?SpecialRpcParams $rpcParams = null;

    #[Ignore]
    protected ?RpcResponse $responseObject = null;

    /**
     * @throws AbstractRpcErrorException
     */
    public function __construct(
        #[Groups([self::S_GROUP])]
        protected string|int $id,
        #[Groups([self::S_GROUP])]
        protected string $method,
        #[Ignore]
        protected array $params = [],
        #[Groups([self::S_GROUP])]
        #[SerializedName('jsonrpc')]
        protected string $version = self::DEFAULT_VERSION,
        #[Ignore]
        protected ?string $rawJson = null
    )
    {
        $this->validate();
        $this->analyzeParams();
    }

    /**
     * @return void
     * @throws AbstractRpcErrorException
     */
    public function validate(): void
    {
        Validator::validate($this->id, RequestRules::assertId())->throw();
        Validator::validate($this->version, RequestRules::assertVersion())->throw();
        Validator::validate($this->method, RequestRules::assertMethod())->throw();
        Validator::validate($this->params, RequestRules::assertParams())->throw();
    }

    protected function analyzeParams(): void
    {
        if (empty($this->id)) {
            $this->id = uniqid();
        }

        $ps = ParamsSplitter::split($this->params);
        $this->rpcParams = $ps->getSpecialParams();
        $this->clearRequire();
        if ($this->hasParams()
            && $matched = preg_grep('/^\@FROM\:/i', array_filter($this->getParams(), 'is_scalar'))
        ) {
            array_walk($matched, function ($value, $paramName) {
                $data = [];
                preg_match('/^\@FROM\:(\w+)\((\w*)\)$/i', $value, $data);
                $requireRequestId = &$data[1];
                $requireFieldName = &$data[2];
                $this->require[$paramName] = new RpcRequestRequire($requireRequestId, $requireFieldName);
                $this->requireIds[$requireRequestId] = $this->requireIds[$requireRequestId] ?? 0;
                $this->requireIds[$requireRequestId]++;
            });
        }
    }

    protected function clearRequire(): void
    {
        $this->require = [];
        $this->requireIds = [];
    }

    public function hasParams(): bool
    {
        return !empty($this->params);
    }

    /**
     * @return array
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * @return array
     */
    #[Groups([self::S_GROUP])]
    #[SerializedName('params')]
    public function getAllParams(): array
    {
        return $this->params + $this->getSpecialParams();
    }

    /**
     * @param string $json
     * @return static
     * @throws RpcJsonParseException
     */
    public static function fromJson(string $json): static
    {
        try {
            return static::fromArray(json_decode($json, true), $json);
        } catch (TypeError $e) {
            throw new RpcJsonParseException('Invalid json data', previous: $e);
        }
    }

    /**
     * @param array $data
     * @return static
     */
    public static function fromArray(array $data): static
    {
        $validator = Validator::validate($data, RequestRules::assertAll());

        $params = $data['params'] ?? [];
        try {
            $sp = ParamsSplitter::split($params);
            $object = new static(
                $data['id'] ?? '',
                (string)$data['method'] ?? '',
                $sp->getParams(),
                $data['jsonrpc'] ?? static::DEFAULT_VERSION,
                json_encode($data)
            );
        } catch (Throwable $e) {
            $ref = new \ReflectionClass(static::class);
            $object = $ref->newInstanceWithoutConstructor();
            $ref->getProperty('id')->setValue($object, $data['id'] ?? uniqid());
            $ref->getProperty('method')->setValue($object, $data['method'] ?? uniqid());
            $ref->getProperty('version')->setValue($object, $data['jsonrpc'] ?? RpcRequest::DEFAULT_VERSION);
        }

        try {
            $validator->throw(RpcBadRequestException::class);
        } catch (AbstractRpcErrorException $e) {
            $object->setError($e);
        }

        if (!$object->hasError() && $sp->getSpecialParams()) {
            $object->setRpcParams($sp->getSpecialParams());
        }
        return $object;
    }

    public function toArray(?NormalizerInterface $normalizer = null, array $context = []): array
    {
        if (is_null($normalizer)) {
            $normalizer = Transformer::getDefault();
        }
        $context = array_merge([
            AbstractNormalizer::GROUPS => [static::S_GROUP],
        ], $context);
        return $normalizer->normalize($this, context: $context);
    }

    /**
     * @return int|string
     */
    public function getId(): int|string
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getMethod(): string
    {
        return $this->method;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return string|null
     */
    public function getRawJson(): ?string
    {
        return $this->rawJson;
    }

    /**
     * @return bool
     */
    public function isAsync(): bool
    {
        return $this->hasRpcParams() && $this->getRpcParams()->hasCallback();
    }

    /**
     * @return string
     * @throws RpcAsyncRequestException
     */
    public function getCallbackUrl(): string
    {
        try {
            return (string)$this->getRpcParams()->getCallbackObject();
        } catch (RpcRuntimeException) {
            throw new RpcAsyncRequestException('Request is not async');
        }
    }

    /**
     * @return CallbackObject
     */
    public function getCallbackObject(): CallbackObject
    {
        if (!$this->isAsync()) {
            throw new RpcAsyncRequestException('Request is not async');
        }
        return $this->getRpcParams()->getCallbackObject();
    }

    public function checkRequireId(string|int $id): bool
    {
        return isset($this->requireIds[$id]);
    }

    public function getCurrentRequireId(): string|int|null
    {
        return array_key_first($this->requireIds);
    }

    public function replaceRequestParam(string $paramName, mixed $newValue): void
    {
        try {
            if (!$this->hasRequire()) {
                throw new RpcRuntimeException(
                    sprintf(
                        'The request does not need to replace parameter "%s".',
                        $paramName
                    )
                );
            }

            if (!isset($this->getRequire()[$paramName])) {
                throw new RpcBadRequestException(
                    sprintf(
                        'The parameter "%s" is not found on request.',
                        $paramName
                    )
                );
            }

            $this->params[$paramName] = $newValue;
            $this->analyzeParams();

        } catch (Throwable $e) {
            $this->error = $e;
        }
    }

    public function refreshRawJson(SerializerInterface $serializer, array $context = []): void
    {
        $context = array_merge([
            AbstractNormalizer::GROUPS => [static::S_GROUP]
        ], $context);
        $this->rawJson = $serializer->serialize($this, 'json', $context);
    }

    public function hasRequire(): bool
    {
        return !empty($this->require);
    }

    /**
     * @return RpcRequestRequire[]
     */
    public function getRequire(): array
    {
        return $this->require;
    }

    /**
     * @return bool
     */
    public function isUserError(): bool
    {
        return $this->error instanceof IUserInputExceptionInterface;
    }

    /**
     * @return bool
     */
    public function isProcedureError(): bool
    {
        return $this->error instanceof IProcedureExceptionInterface;
    }

    /**
     * @return bool
     */
    public function isServerError(): bool
    {
        return $this->error instanceof IServerExceptionInterface;
    }

    public function hasError(): bool
    {
        return $this->error instanceof Throwable;
    }

    /**
     * @return Throwable|null
     */
    public function getError(): ?Throwable
    {
        return $this->error;
    }

    /**
     * @param Throwable $error
     */
    public function setError(Throwable $error): void
    {
        $this->error = $error;
    }

    /**
     * @return bool
     */
    public function isProcessed(): bool
    {
        return !is_null($this->responseObject);
    }

    /**
     * @return RpcResponse|null
     */
    public function getResponseObject(): ?RpcResponse
    {
        return $this->responseObject;
    }

    /**
     * @param RpcResponse $responseObject
     */
    public function setResponse(RpcResponse $responseObject): void
    {
        $this->responseObject = $responseObject;
    }

    /**
     * @return array
     */
    public function getRequireIds(): array
    {
        return $this->requireIds;
    }

    /**
     * @param array $requireIds
     */
    public function setRequireIds(array $requireIds): void
    {
        $this->requireIds = $requireIds;
    }

    /**
     * @return bool
     */
    public function hasRpcParams(): bool
    {
        return !is_null($this->rpcParams);
    }

    /**
     * @return ?SpecialRpcParams
     */
    public function getRpcParams(): ?SpecialRpcParams
    {
        return $this->rpcParams;
    }

    /**
     * @param SpecialRpcParams $rpcParams
     */
    public function setRpcParams(SpecialRpcParams $rpcParams): void
    {
        $this->rpcParams = $rpcParams;
    }

    public function getSpecialParams(): array
    {
        return $this->getRpcParams() ? $this->getRpcParams()->toArray() : [];
    }
}
