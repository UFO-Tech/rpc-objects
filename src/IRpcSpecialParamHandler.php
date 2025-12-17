<?php

namespace Ufo\RpcObject;

interface IRpcSpecialParamHandler
{
    public function setParam(string $name, mixed $value): static;

    public function resetParams(): static;

    public function getSpecialParams(): array;
}