<?php

namespace Ufo\RpcObject;

use Symfony\Component\Serializer\Attribute\Groups;
use Symfony\Component\Serializer\Attribute\Ignore;
use Symfony\Component\Serializer\Attribute\SerializedName;
use Symfony\Component\Serializer\SerializerInterface;
use Ufo\DTO\Helpers\TypeHintResolver;
use Ufo\RpcError\AbstractRpcErrorException;
use Ufo\RpcError\WrongWayException;
use Ufo\RpcObject\RPC\Cache;
use Ufo\RpcObject\Transformer\RpcResponseContextBuilder;
use Ufo\RpcObject\Transformer\Transformer;

class RpcResponse
{
    const string IS_RESULT = 'result';
    const string IS_ERROR = 'error';
    const string IS_LIST = 'list';
    const string IS_DETAIL = 'detail';

    const string IS_GROUP = 'group';
    const string IS_GROUP_2 = 'group_2';
    const string IS_GROUP_3 = 'group_3';
    const string IS_GROUP_4 = 'group_4';
    const string IS_GROUP_5 = 'group_5';

    #[Ignore]
    protected SerializerInterface $transformer;
    #[Ignore]
    protected RpcResponseContextBuilder $contextBuilder;

    public function __construct(
        #[Groups([self::IS_RESULT, self::IS_ERROR])]
        protected string|int $id,
        #[Groups([self::IS_RESULT])]
        protected string|int|float|bool|array|object|null $result = [],
        #[Groups([self::IS_ERROR])]
        protected ?RpcError $error = null,
        #[Groups([self::IS_RESULT, self::IS_ERROR])]
        #[SerializedName('jsonrpc')]
        protected string $version = RpcRequest::DEFAULT_VERSION,
        #[Ignore]
        protected ?RpcRequest $requestObject = null,
        #[Ignore]
        protected ?Cache $cache = null,
        ?RpcResponseContextBuilder $contextBuilder = null
    ) {
        $this->contextBuilder = $contextBuilder ?? new RpcResponseContextBuilder();
        $this->transformer = Transformer::getDefault();
    }

    /**
     * @return int|string
     */
    public function getId(): int|string
    {
        return $this->id;
    }

    /**
     * @param bool $asIs
     * @return mixed
     */
    public function getResult(bool $asIs = false): mixed
    {
        return $asIs ? $this->result : match (gettype($this->result)) {
            TypeHintResolver::OBJECT->value => $this->normalizeResult($this->result),
            TypeHintResolver::ARRAY->value => array_map(function ($data) {
                try {
                    return $this->normalizeResult($data);
                } catch (\Throwable $e) {
                    return $data;
                }
            }, $this->result),
            default => $this->result
        };
    }

    protected function normalizeResult(object $result): array|string|int
    {
        return $this->transformer->normalize($result, context: $this->contextBuilder->removeParent()->toArray());
    }

    /**
     * @return ?RpcError
     */
    public function getError(): ?RpcError
    {
        return $this->error;
    }

    /**
     * @throws WrongWayException
     * @throws AbstractRpcErrorException
     */
    public function throwError(): void
    {
        if (!is_null($this->error)) {
            if ($this->error->getData() instanceof \Throwable) {
                throw AbstractRpcErrorException::fromThrowable($this->error->getData());
            } else {
                throw AbstractRpcErrorException::fromCode($this->error->getCode(), $this->error->getMessage());
            }
        }
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @return ?RpcRequest
     */
    public function getRequestObject(): ?RpcRequest
    {
        return $this->requestObject;
    }

    #[Ignore]
    public function getResponseSignature(): string
    {
        return is_null($this->error) ? static::IS_RESULT : static::IS_ERROR;
    }

    #[Ignore]
    public function getCacheInfo(): ?Cache
    {
        return $this->cache;
    }

}
