<?php

namespace Ufo\RpcObject;

use Symfony\Component\Serializer\Annotation\Groups;

class RpcError
{
    const IS_ERROR = 'error';

    public function __construct(
        #[Groups([self::IS_ERROR])]
        protected int $code,

        #[Groups([self::IS_ERROR])]
        protected string $message,

        #[Groups([self::IS_ERROR])]
        protected \Throwable|array $data
    )
    {
    }

    /**
     * @return int
     */
    public function getCode(): int
    {
        return $this->code;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @return \Throwable|array
     */
    public function getData(): \Throwable|array
    {
        return $this->data;
    }

    public static function fromThrowable(\Throwable $e): static
    {
        return new static($e->getCode(), $e->getMessage(), $e);
    }

}
