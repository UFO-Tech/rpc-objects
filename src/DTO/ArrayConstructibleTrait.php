<?php

namespace Ufo\RpcObject\DTO;

use ReflectionException;

trait ArrayConstructibleTrait
{
    /**
     * @throws ReflectionException
     */
    public static function fromArray(array $data, array $renameKey = []): self
    {
        return DTOTransformer::fromArray(static::class, $data, $renameKey);
    }
}