<?php

namespace Ufo\RpcObject\Rules;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Constraints as Assert;

class RequestRules
{
    public static function assertAll(): Constraint
    {
        return new Assert\Collection([
            'fields' => [
                'id' => RequestRules::assertId(),
                'method' => RequestRules::assertMethod(),
                'jsonrpc' => RequestRules::assertVersion(),
                'params' => RequestRules::assertParams(),
            ],
            'allowExtraFields' => true
        ]);
    }


    public static function assertId(): Constraint
    {
        return new Assert\Optional([
            new Assert\Type(['int', 'string']),
            new Assert\NotBlank(),
        ]);
    }

    public static function assertVersion(): Constraint
    {
        return new Assert\Optional([
            new Assert\Type('string'),
            new Assert\Regex('/\d\.\d/'),
        ]);
    }

    public static function assertMethod(): array
    {
        return [
            new Assert\Type('string'),
            new Assert\Required(),
            new Assert\NotBlank(),
        ];
    }

    public static function assertParams(): Constraint
    {
        return new Assert\Optional([
            new Assert\Type('array'),
            new Assert\Count(['min' => 1]),
            new Assert\Collection([
                'fields' => SpecialParamsRules::assertAllParams(),
                'allowExtraFields' => true
            ]),
        ]);
    }


}
