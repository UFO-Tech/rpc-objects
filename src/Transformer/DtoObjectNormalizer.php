<?php

declare(strict_types = 1);

namespace Ufo\RpcObject\Transformer;

use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Interfaces\IArrayConvertible;
use UnitEnum;

class DtoObjectNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{
    public const string AS_SMART = 'asSmart';
    public function __construct(
        protected AbstractObjectNormalizer $normalizer
    ) {}

    public function normalize(mixed $data, ?string $format = null, array $context = []): ?array
    {
        if ($data instanceof IArrayConvertible) {
            return $data->toArray();
        }

        $result = $this->normalizer->normalize($data, $format, $context);

        if ($result instanceof \ArrayObject) {
            $result = $result->getArrayCopy();
        }

        if (($context[static::AS_SMART] ?? false) && is_object($data) && is_array($result)) {
            $refClass = new \ReflectionClass($data);
            $result[DTOTransformer::DTO_CLASSNAME] = $refClass->getShortName();
        }

        return $result;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return !$data instanceof UnitEnum && $this->normalizer->supportsNormalization($data, $format, $context);
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->normalizer->getSupportedTypes($format);
    }

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): object
    {
        return DTOTransformer::fromArray($type, $data, namespaces: $context['namespaces'] ?? []);
    }

    public function supportsDenormalization(mixed $data, string $type, ?string $format = null, array $context = []): bool
    {
        return true;
    }

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->normalizer->setSerializer($serializer);
    }
}