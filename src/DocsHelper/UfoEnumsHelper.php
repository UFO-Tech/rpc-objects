<?php

namespace Ufo\RpcObject\DocsHelper;

use Ufo\RpcError\RpcRuntimeException;

class UfoEnumsHelper
{
    const string METHOD_VALUES = 'values';
    const string METHOD_FROM_VALUE = 'fromValue';
    const string METHOD_TRY_FROM_VALUE = 'tryFromValue';

    public static function generateEnumSchema(string $enumFQCN, string $method = self::METHOD_VALUES): array
    {
        $data =  array_column($enumFQCN::cases(), 'value', 'name');

        if (method_exists($enumFQCN, $method) && method_exists($enumFQCN, static::METHOD_FROM_VALUE)) {
            foreach ($enumFQCN::values() as $value) {
                $methodTry = static::METHOD_TRY_FROM_VALUE;
                $enum = call_user_func([$enumFQCN, $methodTry],$value) ?? throw new RpcRuntimeException('Invalid value "' . $value . '" for enum ' . $enumFQCN);
                $data[$enum->name] = $value;
            }
        }
        $rules[XUfoValuesEnum::ENUM->value] = $data;
        $rules[XUfoValuesEnum::ENUM_NAME->value] = (new \ReflectionClass($enumFQCN))->getShortName();

        return $rules;
    }
}
