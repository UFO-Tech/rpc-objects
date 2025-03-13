<?php

namespace Ufo\RpcObject;

use Ufo\RpcObject\Rules\SpecialParamsRules;
use Ufo\RpcObject\Rules\Validator\Validator;

enum SpecialRpcParamsEnum: string
{
    /**
     * Timeout for request. Second
     */
    const int DEFAULT_TIMEOUT = 10;
    const string PREFIX = '$rpc';

    case TIMEOUT = 'timeout';
    case CALLBACK = 'callback';
    case PARENT_REQUEST = 'rayId';

    public static function fromArray(array $data): SpecialRpcParams
    {
        Validator::validate($data, SpecialParamsRules::assertAllParamsCollection())->throw();

        return new SpecialRpcParams(
            $data[self::CALLBACK->value] ?? null,
            $data[self::TIMEOUT->value] ?? self::DEFAULT_TIMEOUT,
            $data[self::PARENT_REQUEST->value] ?? null,
        );
    }

}
