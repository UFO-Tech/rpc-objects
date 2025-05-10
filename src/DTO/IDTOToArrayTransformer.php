<?php

namespace Ufo\RpcObject\DTO;

interface IDTOToArrayTransformer
{
    public static function toArray(object $dto): array;
}