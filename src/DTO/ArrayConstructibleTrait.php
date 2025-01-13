<?php

namespace Ufo\RpcObject\DTO;

use ReflectionException;

trait ArrayConstructibleTrait
{
    /**
     * @throws ReflectionException
     */
    public static function fromArray(array $data): self
    {
        return DTOTransformer::fromArray(self::class, $data);
    }
}