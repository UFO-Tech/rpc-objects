<?php

namespace Ufo\RpcObject\Events;

use ReflectionException;
use Symfony\Contracts\EventDispatcher\Event;

class BaseRpcEvent extends Event
{
    const string NAME = 'rpc.noname';

    public function getEventName(): string
    {
        return static::NAME;
    }
}