<?php

namespace Ufo\RpcObject\RPC;

use Attribute;
use Ufo\RpcObject\Transformer\Transformer;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final readonly class Assertions
{
    public string $constructorArgs;

    public function toArray(): array
    {
        $array = [
            'constructor' => $this->constructorArgs,
        ];
        foreach ($this->assertions as $assertion) {
            $array['payload'][] = Transformer::getDefault()->normalize($assertion);
        }

        return $array;
    }

}