<?php

namespace Ufo\RpcObject\DTO;

use Attribute;
use Ufo\RpcError\RpcBadParamException;
use Ufo\RpcObject\RPC\Assertions;
use Ufo\RpcObject\RPC\DTO;
use Ufo\RpcObject\Rules\Validator\Validator;

enum DTOAttributesEnum: string
{
    case ASSERTIONS = Assertions::class;
    case DTO = DTO::class;

    public function process(Attribute $attribute, mixed $value): mixed
    {
        return match ($this) {
            self::ASSERTIONS => $this->validate($attribute, $value),
            self::DTO => $this->transformDto($attribute, $value),
            default => $value,
        };
    }
    
    protected function transformDto(Assertions $attribute, mixed $value): object
    {
        return DTOTransformer::fromArray($value);
    }
    
    protected function validate(Assertions $attribute, mixed $value): mixed
    {
        $assertions = $attribute->assertions;
        $validator = Validator::validate($value, $assertions);

        if ($validator->hasErrors()) {
            $errorMessage = $property->getName() .  $validator->getCurrentError();
            throw new RpcBadParamException($errorMessage);
        }
        return $value;
    }
}
