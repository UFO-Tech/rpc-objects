<?php

namespace Ufo\RpcObject\Transformer;

use BackedEnum;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Exceptions\BadParamException;
use UnitEnum;

class EnumNormalizer implements NormalizerInterface, DenormalizerInterface
{
    protected const array SUPPORTED_TYPES = [
        UnitEnum::class => true,
        BackedEnum::class => true,
    ];

    public function normalize(mixed $data, ?string $format = null, array $context = []): array|string|int|float|bool|\ArrayObject|null
    {
        if ($data instanceof UnitEnum) {
            $data = $data instanceof BackedEnum ? $data->value : $data->name;
        }

        return $data;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof UnitEnum;
    }

    public function getSupportedTypes(?string $format): array
    {
        return static::SUPPORTED_TYPES;
    }

    /**
     * @param mixed $data
     * @param string $type
     * @param string|null $format
     * @param array $context
     * @return string|int|UnitEnum|BackedEnum
     */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): string|int|UnitEnum|BackedEnum
    {
        try {
            return DTOTransformer::transformEnum($type, $data);
        } catch (BadParamException $e) {
            throw new UnexpectedValueException($e->getMessage(), $e->getCode(), $e);
        }
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return is_string($data) || is_int($data) || $data instanceof UnitEnum;
    }
}
