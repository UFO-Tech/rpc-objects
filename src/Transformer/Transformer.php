<?php

namespace Ufo\RpcObject\Transformer;

use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\Mapping\Loader\AttributeLoader;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\{
    ArrayDenormalizer,
    DateTimeNormalizer,
    DenormalizerInterface,
    NormalizerInterface,
    UidNormalizer,
    ObjectNormalizer};
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerInterface;

class Transformer
{
    protected static ?Transformer $instance = null;
    protected SerializerInterface $serializer;

    /**
     * @param SerializerInterface $serializer
     */
    protected function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public static function getDefault(): SerializerInterface
    {
        if (is_null(static::$instance)) {
            $encoders = [new JsonEncoder()];
            $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
            $metadataAwareNameConverter = new MetadataAwareNameConverter($classMetadataFactory);
            $propertyAccessor = new ReflectionExtractor();
            $objectNormaliser = new ObjectNormalizer(
                $classMetadataFactory,
                $metadataAwareNameConverter,
                propertyTypeExtractor: $propertyAccessor
            );
            $normalizers = [
                new ArrayDenormalizer(),
                new ConstraintObjectNormalizer($objectNormaliser),
                new DateTimeNormalizer(),
                new UidNormalizer(),
                $objectNormaliser,
            ];
            static::$instance = new static(new Serializer($normalizers, $encoders));
        }
        return static::$instance->getSerializer();
    }

    /**
     * @return SerializerInterface
     */
    public function getSerializer(): SerializerInterface
    {
        return $this->serializer;
    }

}
