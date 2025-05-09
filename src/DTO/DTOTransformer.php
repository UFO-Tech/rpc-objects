<?php

namespace Ufo\RpcObject\DTO;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionException;
use Ufo\RpcObject\Helpers\TypeHintResolver;
use Ufo\RpcError\RpcBadParamException;

use function array_key_exists;
use function is_null;

class DTOTransformer
{
    /**
     * Converts a DTO object to an associative array.
     *
     * @param object $dto The object to convert.
     * @return array An associative array of the object's properties.
     */
    public static function toArray(object $dto): array
    {
        $reflection = new ReflectionClass($dto);
        $properties = $reflection->getProperties();
        $array = [];

        foreach ($properties as $property) {
            $value = $property->getValue($dto);
            $value = static::convertValue($value);
            $array[$property->getName()] = $value;
        }

        return $array;
    }

    protected static function convertValue(mixed $value): mixed
    {
        return match (gettype($value)) {
            TypeHintResolver::ARRAY->value => static::mapArrayWithKeys($value),
            TypeHintResolver::OBJECT->value => $value instanceof IArrayConvertible ? $value->toArray() : static::toArray($value),
            default => $value,
        };
    }

    protected static function mapArrayWithKeys(array $array): array
    {
        $result = [];
        foreach ($array as $k => $v) {
            $result[$k] = static::convertValue($v);
        }
        return $result;
    }

    /**
     * Creates a DTO object from an associative array.
     *
     * @param string $classFQCN The name of the class to instantiate.
     * @param array $data The array of data to populate the object.
     * @param array<string|string> $renameKey The array of key for replace in data.
     * @return object The created object.
     * @throws ReflectionException|InvalidArgumentException|RpcBadParamException
     */
    public static function fromArray(string $classFQCN, array $data, array $renameKey = []): object
    {
        $instance = null;
        $reflectionClass = new ReflectionClass($classFQCN);
        $constructParams = [];
        $hasReadonly = false;
        $constructor = $reflectionClass->getConstructor();

        if ($constructor && $constructor->isPublic()) {
            foreach ($constructor->getParameters() as $param) {
                $key = static::getPropertyKey($param, $renameKey);
                if (!$key) continue;
                $constructParams[$key] = static::extractValue($key, $data, $param);
                try {
                    if ($reflectionClass->getProperty($key)->isReadOnly()) {
                        $hasReadonly = true;
                    }
                } catch (\Throwable) {}
            }
            if ($hasReadonly) $instance = $reflectionClass->newInstanceArgs($constructParams);
        }
        $instance = $instance ?? $reflectionClass->newInstanceWithoutConstructor();

        foreach ($reflectionClass->getProperties() as $property) {
            $key = static::getPropertyKey($property, $renameKey);

            if (!$key || $property->isReadOnly() || ($hasReadonly && array_key_exists($key, $constructParams))) {
                continue;
            }

            $value = static::extractValue($key, $data, $property);
            $property->setValue($instance, $value);
        }

        return $instance;
    }

    /**
     * @throws ReflectionException
     * @throws RpcBadParamException
     */
    protected static function extractValue(
        string $key,
        array $data,
        ReflectionParameter|ReflectionProperty $ref
    ): mixed {
        if (isset($data[$key])) {
            return static::checkAttributes($ref, $data[$key]);
        }

        return match (true) {
            $ref instanceof ReflectionParameter => $ref->isOptional()
                ? $ref->getDefaultValue()
                : throw new InvalidArgumentException("Missing required key for constructor param: '$key'"),

            $ref instanceof ReflectionProperty => (function () use ($ref, $key) {
                $instance = $ref->getDeclaringClass()->newInstanceWithoutConstructor();
                try {
                    return $ref->getValue($instance);
                } catch (\Throwable) {
                    if (!$ref->isInitialized($instance)) {
                        foreach ($ref->getDeclaringClass()->getConstructor()->getParameters() as $p) {
                            if ($p->getName() === $key && $p->isOptional()) {
                                return $p->getDefaultValue();
                            }
                        }
                    }
                    throw new InvalidArgumentException("Missing required key for property: '$key'");
                }
            })(),

            default => throw new InvalidArgumentException('Unsupported reflection type'),
        };
    }


    /**
     * @throws RpcBadParamException
     */
    protected static function checkAttributes(ReflectionProperty|ReflectionParameter $property, mixed $value): mixed
    {
        $attributes = $property->getAttributes();
        foreach ($attributes as $attribute) {
            if (!isset($attribute->name)) continue;
            try {
                $value = DTOAttributesEnum::from($attribute->name)->process($attribute->newInstance(), $value, $property);
            } catch (\ValueError) {}
        }
        return $value;
    }

    protected static function getPropertyKey(ReflectionProperty|ReflectionParameter $property, array $renameKey): ?string
    {
        $pName = $property->getName();
        return array_key_exists($pName, $renameKey) ? $renameKey[$pName] : $pName;
    }

}