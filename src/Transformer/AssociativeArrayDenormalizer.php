<?php

namespace Ufo\RpcObject\Transformer;

use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class AssociativeArrayDenormalizer implements DenormalizerInterface
{
    public function supportsDenormalization($data, $type, $format = null, array $context = []): bool
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
            $result[$key] = $value;
        }

        return $result;
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['*' => true];
    }
}

