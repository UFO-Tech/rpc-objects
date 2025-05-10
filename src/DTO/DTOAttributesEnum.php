<?php

namespace Ufo\RpcObject\DTO;

use ReflectionParameter;
use ReflectionProperty;
use Ufo\RpcError\RpcBadParamException;
use Ufo\RpcObject\RPC\Assertions;
use Ufo\RpcObject\RPC\DTO;
use Ufo\RpcObject\Rules\Validator\Validator;

use function class_implements;

enum DTOAttributesEnum: string
{
    case ASSERTIONS = Assertions::class;
    case DTO = DTO::class;

    public function process(object $attribute, mixed $value, ReflectionProperty|ReflectionParameter $property): mixed
    {
        return match ($this) {
            self::ASSERTIONS => $this->validate($attribute, $value, $property),
            self::DTO => $this->resolveDTO($attribute, $value, $property),
            default => $value,
        };
    }

    protected function resolveDTO(DTO $attribute, mixed $value, ReflectionProperty|ReflectionParameter $property): array|object
    {
        if ($attribute->collection) {
            return $this->transformDTOCollection($attribute, $value, $property);
        }
        return $this->transformDto($attribute, $value, $property);
    }

    protected function transformDTOCollection(DTO $attribute, mixed $value, ReflectionProperty|ReflectionParameter $property): array
    {
        $result = [];
        foreach ($value as $key => $item) {
            $result[$key] = $this->transformDto($attribute, $item, $property);
        }
        return $result;
    }

    /**
     * @throws RpcBadParamException
     * @throws NotSupportDTOException
     */
    protected function transformDto(DTO $attribute, mixed $value, ReflectionProperty|ReflectionParameter $property): object
    {
         if ($dtoTransformerFQCN = $attribute->transformerFQCN) {
             $implements = class_implements($dtoTransformerFQCN);
             if ($implements[IDTOFromArrayTransformer::class] ?? false) {
                 /**
                  * @var IDTOFromArrayTransformer $dtoTransformerFQCN
                  */
                 if (!$dtoTransformerFQCN::isSupportClass($attribute->dtoFQCN)) {
                     throw new NotSupportDTOException($dtoTransformerFQCN . ' is not support transform for ' . $attribute->dtoFQCN);
                 }
                 return $dtoTransformerFQCN::fromArray($attribute->dtoFQCN, $value, $attribute->renameKeys);
             }
         }
        return DTOTransformer::fromArray($attribute->dtoFQCN, $value, $attribute->renameKeys);
    }

    protected function validate(Assertions $attribute, mixed $value, ReflectionProperty|ReflectionParameter $property): mixed
    {
        $assertions = $attribute->assertions;
        $validator = Validator::validate($value, $assertions);

        if ($validator->hasErrors()) {
            $errorMessage = $property->getName() . $validator->getCurrentError();
            throw new RpcBadParamException($errorMessage);
        }
        return $value;
    }
}
