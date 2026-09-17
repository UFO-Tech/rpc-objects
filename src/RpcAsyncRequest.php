<?php

namespace Ufo\RpcObject;

use JetBrains\PhpStorm\Deprecated;

readonly class RpcAsyncRequest
{

    public function __construct(
        public RpcRequest $rpcRequest,
        public string $token = '',
        public array $meta = [],
    ) {}

    /**
     * @deprecated Use the rpcRequest property directly.
     * todo delete in 4.0
     */
    #[Deprecated(
        reason: 'Use the rpcRequest property directly. Will be removed in 4.0.0.',
        replacement: '%class%->rpcRequest',
    )]
    public function getRpcRequest(): RpcRequest
    {
        return $this->rpcRequest;
    }

}
