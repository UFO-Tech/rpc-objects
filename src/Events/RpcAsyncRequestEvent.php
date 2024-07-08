<?php

namespace Ufo\RpcObject\Events;

class RpcAsyncRequestEvent extends RpcRequestEvent
{
    public const string NAME = RpcEvent::REQUEST_ASYNC;

}