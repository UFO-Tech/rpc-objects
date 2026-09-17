<?php

namespace Ufo\RpcObject\Transformer;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class AssociativeArrayDenormalizer implements DenormalizerInterface
{
    public function __construct(protected DenormalizerInterface $denormalizer) {}

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_array($data) && $this->isAssociativeArray($data);
    }

    private function isAssociativeArray(array $array): bool
    {
        // Якщо ключі масиву не є послідовними числовими індексами, то це асоціативний масив
        return array_keys($array) !== range(0, count($array) - 1);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        if (!is_array($data)) {
            throw new InvalidArgumentException('Data expected to be an array.');
        }

        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value) && isset($context['value_type'])) {
                $result[$key] = $this->denormalizer->denormalize(
                    $value,
                    $this->resolveValueTypeClass($context['value_type']),
                    $format,
                    $context
                );
            } else {
                $result[$key] = $value;
            }
        }


        return $result;
    }

    private function resolveValueTypeClass(mixed $valueType): string
    {
        return match (true) {
            is_string($valueType) => $valueType,
            is_object($valueType) && method_exists($valueType, 'getClassName') => (string)$valueType->getClassName(),
            default => throw new InvalidArgumentException('Context "value_type" expected to be a class name or a type object exposing getClassName().')
        };
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['*' => true];
    }
}
