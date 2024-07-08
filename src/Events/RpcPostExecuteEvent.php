<?php

namespace Ufo\RpcObject\Events;

class RpcPostExecuteEvent extends BaseRpcEvent
{
    public const string NAME = RpcEvent::POST_EXECUTE;

    public function __construct(
        public mixed $result
    ) {}

}