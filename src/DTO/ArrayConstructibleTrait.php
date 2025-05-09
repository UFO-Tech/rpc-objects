<?php

namespace Ufo\RpcObject\DTO;

use ReflectionException;

trait ArrayConstructibleTrait
{
    /**
     * @throws ReflectionException
     */
    public static function fromArray(array $data, array $renameKey = []): static
    {
        return DTOTransformer::fromArray(static::class, $data, $renameKey);
    }
}