<?php

namespace Ufo\RpcObject\Events;

use Ufo\RpcObject\RpcResponse;

class RpcResponseEvent extends BaseRpcEvent
{
    public const string NAME = RpcEvent::RESPONSE;

    public function __construct(
        public RpcResponse $response
    ) {}

}