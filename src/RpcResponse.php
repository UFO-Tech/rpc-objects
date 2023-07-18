<?php

namespace Ufo\RpcObject;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Ufo\RpcError\AbstractRpcErrorException;
class RpcResponse
{
    const IS_RESULT = 'result';
    const IS_ERROR = 'error';
    
    public function __construct(
        #[Groups([self::IS_RESULT, self::IS_ERROR])]
        protected string|int $id,

        #[Groups([self::IS_RESULT])]
        protected array|string $result = [],

        #[Groups([self::IS_ERROR])]
        protected ?RpcError $error = null,

        #[Groups([self::IS_RESULT, self::IS_ERROR])]
        #[SerializedName('jsonrpc')]
        protected string $version = RpcRequest::DEFAULT_VERSION,

        #[Ignore] protected ?RpcRequest $requestObject = null
    )
    {
    }

    /**
     * @return int|string
     */
    public function getId(): int|string
    {
        return $this->id;
    }

    /**
     * @return array|string
     */
    public function getResult(): array|string
    {
        return $this->result;
    }

    /**
     * @return ?RpcError
     */
    public function getError(): ?RpcError
    {
        return $this->error;
    }

    public function throwError(): void
    {
        if (!is_null($this->error)) {
            if ($this->error->getData() instanceof \Throwable) {
                throw new AbstractRpcErrorException::fromThrowable($this->error->getData());
            } else {
                throw new AbstractRpcErrorException::fromCode($this->error->getCode(), $this->error->getMessage());
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

    #[Ignore] public function getResponseSignature(): string
    {
        return is_null($this->error) ? static::IS_RESULT : static::IS_ERROR;
    }
}
