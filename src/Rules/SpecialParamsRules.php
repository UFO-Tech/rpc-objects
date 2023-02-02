<?php

namespace Ufo\RpcObject\Rules;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

class SpecialParamsRules
{
    const PARAMS = [
        'callback',
        'timeout'
    ];

    public static function assertAllParams(): array
    {
        $params = [];
        foreach (static::PARAMS as $param) {
            $params['$rpc.' . $param] = call_user_func([static::class, 'assert' . strtoupper($param)]);
        }

        return $params;
    }

    public static function assertAllParamsCollection(): Constraint
    {
        $params = [];
        foreach (static::PARAMS as $param) {
            $params['$rpc.' . $param] = call_user_func([static::class, 'assert' . strtoupper($param)]);
        }
        return new Assert\Collection($params);
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
