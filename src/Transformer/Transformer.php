<?php

namespace Ufo\RpcObject\Transformer;

use Symfony\Component\PropertyInfo\Extractor\ConstructorExtractor;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\Extractor\SerializerExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
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
            $encoders = ['json' => new JsonEncoder()];
            $classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
            $metadataAwareNameConverter = new MetadataAwareNameConverter($classMetadataFactory);

            $phpDocExtractor = new PhpDocExtractor();
            $propertyInfoExtractor = new PropertyInfoExtractor(
                typeExtractors: [
                    new ConstructorExtractor([$phpDocExtractor]),
                    $phpDocExtractor,
                    new ReflectionExtractor(),
                    new SerializerExtractor($classMetadataFactory),
                ],
            );

            $objectNormaliser = new ObjectNormalizer(
                $classMetadataFactory,
                $metadataAwareNameConverter,
                propertyTypeExtractor: $propertyInfoExtractor,
            );
            $arrayDenormalizer = new ArrayDenormalizer();
            $normalizers = [
                new DateTimeNormalizer(),
                new UidNormalizer(),
                new ConstraintObjectNormalizer($objectNormaliser),
                $objectNormaliser,
                new AssociativeArrayDenormalizer($objectNormaliser),
                $arrayDenormalizer,
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
