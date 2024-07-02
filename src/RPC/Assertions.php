<?php

namespace Ufo\RpcObject\RPC;

use Attribute;
use Symfony\Component\Validator\Constraint;
use Ufo\RpcObject\Transformer\Transformer;

#[Attribute(Attribute::TARGET_PARAMETER)]
final readonly class Assertions
{
    public string $constructorArgs;
    /**
     * @param Constraint[] $assertions
     */
    public function __construct(
        readonly public array $assertions
    ) {}

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