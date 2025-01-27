<?php

namespace Ufo\RpcObject\DTO;

use ReflectionClass;
use ReflectionException;

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
            if ($value instanceof IArrayConvertible) {
                $value = $value->toArray();
            }
            $array[$property->getName()] = $value;
        }

        return $array;
    }

    /**
     * Creates a DTO object from an associative array.
     *
     * @param string $classFQCN The name of the class to instantiate.
     * @param array $data The array of data to populate the object.
     * @return object The created object.
     * @throws ReflectionException
     */
    public static function fromArray(string $classFQCN, array $data): object
    {
        $reflection = new ReflectionClass($classFQCN);

        $instance = $reflection->newInstanceWithoutConstructor();
        foreach ($reflection->getProperties() as $property) {
            $key = $property->getName();

            if (!($data[$key] ?? false)) {
                if (!$property->hasDefaultValue()) {
                    throw new \InvalidArgumentException("Missing required key: '$key'");
                }
                continue;
            }

            $property->setValue($instance, $data[$key]);
        }

        return $instance;
    }
}