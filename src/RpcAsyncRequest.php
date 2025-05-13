<?php

namespace Ufo\RpcObject;

class RpcAsyncRequest
{

    public function __construct(
        protected RpcRequest $rpcRequest,
        public readonly string $token = ''
    ) {}

    /**
     * @return RpcRequest
     */
    public function getRpcRequest(): RpcRequest
    {
        return $this->rpcRequest;
    }

}
