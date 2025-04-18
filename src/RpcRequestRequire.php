<?php

namespace Ufo\RpcObject;

class RpcRequestRequire
{

    public function __construct(protected string|int $responseId, protected string $responseFieldName) {}

    /**
     * @return string|int
     */
    public function getResponseId(): string|int
    {
        return $this->responseId;
    }

    /**
     * @return string
     */
    public function getResponseFieldName(): string
    {
        return $this->responseFieldName;
    }
    
}
