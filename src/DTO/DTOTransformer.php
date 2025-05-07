<?php

namespace Ufo\RpcObject\DTO;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionParameter;
use ReflectionProperty;
use ReflectionException;
use Ufo\RpcObject\Helpers\TypeHintResolver;
use Ufo\RpcError\RpcBadParamException;

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
        $reflection = new ReflectionClass($classFQCN);
        $constructor = $reflection->getConstructor();
        $constructParams = [];

        if ($constructor && $constructor->isPublic()) {
            foreach ($constructor->getParameters() as $param) {
                $key = static::getPropertyKey($param, $renameKey);
                $constructParams[$key] = static::extractValue($key, $data, $param);
            }
            $instance = $reflection->newInstance($constructParams);
        }
        $instance = $instance ?? $reflection->newInstanceWithoutConstructor();

        foreach ($reflection->getProperties() as $property) {
            $key = static::getPropertyKey($property, $renameKey);

            if ($property->isReadOnly() || isset($constructParams[$key])) {
                continue;
            }

            $property->setValue($instance, static::extractValue($key, $data, $property));
        }

        return $instance;
    }

    protected static function getPropertyKey(ReflectionProperty|ReflectionParameter $property, array $renameKey): string
    {
        return $renameKey[$property->getName()] ?? $property->getName();
    }

    /**
     * @throws ReflectionException
     * @throws RpcBadParamException
     */
    protected static function extractValue(string $key, array $data, ReflectionParameter|ReflectionProperty $ref): mixed
    {
        if (!($data[$key] ?? null)) {
            if (
                ($ref instanceof ReflectionParameter && !$ref->isOptional())
                || ($ref instanceof ReflectionProperty && !$ref->hasDefaultValue())
            ) {
                throw new InvalidArgumentException("Missing required key: '$key'");
            }
            return $ref->getDefaultValue();
        }

        return self::checkAttributes($ref, $data[$key]);
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
}