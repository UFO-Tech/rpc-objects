<?php

namespace Ufo\RpcObject\DTO;

trait ArrayConvertibleTrait
{
    public function toArray(): array
    {
        return DTOTransformer::toArray($this);
    }
}