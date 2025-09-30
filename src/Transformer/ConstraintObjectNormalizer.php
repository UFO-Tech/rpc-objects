<?php

namespace Ufo\RpcObject\Transformer;

use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraint;

use function get_class;

class ConstraintObjectNormalizer implements NormalizerInterface, DenormalizerInterface, SerializerAwareInterface
{

    public function __construct(
        protected DtoObjectNormalizer $normalizer
    ) {}

    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): mixed
    {
        return $this->normalizer->denormalize($data, $type, $format, $context);
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = []
    ): bool
    {
        return $this->normalizer->supportsDenormalization($data, $type, $format, $context);
    }

    public function normalize(
        mixed $data,
        ?string $format = null,
        array $context = []
    ): array|string|int|float|bool|\ArrayObject|null
    {
        return [
            'class' => $data::class,
            'context' => $this->normalizer->normalize($data, $format, $context)
        ];
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $data instanceof Constraint;
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['object' => true];
    }

    public function setSerializer(SerializerInterface $serializer): void
    {
        $this->normalizer->setSerializer($serializer);
    }

}