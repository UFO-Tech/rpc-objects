<?php

namespace Ufo\RpcObject\RPC;

use Attribute;
use Ufo\DTO\Attributes\AttrAssertions;
use Ufo\RpcObject\Transformer\Transformer;

#[Attribute(Attribute::TARGET_PARAMETER | Attribute::TARGET_PROPERTY)]
final readonly class Assertions extends AttrAssertions
{
    public function __construct(array $assertions, public string $constructorArgs = '')
    {
        parent::__construct($assertions);
    }

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