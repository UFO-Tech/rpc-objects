<?php

namespace Ufo\RpcObject\Rules;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;
use Ufo\RpcObject\SpecialRpcParamsEnum;

use function method_exists;
use function strtoupper;

class SpecialParamsRules
{
    public static function assertAllParams(): array
    {
        $params = [];
        foreach (SpecialRpcParamsEnum::cases() as $param) {
            $method = 'assert' . strtoupper($param->value);
            if (!method_exists(self::class, $method)) continue;
            $params[$param->value] = call_user_func([static::class, $method]);
        }
        return $params;
    }

    public static function assertAllParamsCollection(): Constraint
    {
        return new Assert\Collection(static::assertAllParams());
    }

    public static function assertCallback(): Constraint
    {
        return new Assert\Optional(CallbackRules::assertUrl());
    }

    public static function assertTimeout(): Constraint
    {
        return new Assert\Optional([
            new Assert\NotBlank(),
            new Assert\Type(['int', 'float']),
            new Assert\Range([
                'min' => 10,
                'max' => 120
            ])
        ]);
    }
}
