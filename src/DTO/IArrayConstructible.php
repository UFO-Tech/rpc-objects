<?php

namespace Ufo\RpcObject\DTO;

interface IArrayConstructible
{
    public static function fromArray(array $data, array $renameKey = []): self;
}