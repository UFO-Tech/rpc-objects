<?php

namespace Ufo\RpcObject\Tests\Transformer;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\UnexpectedValueException;
use Ufo\RpcObject\Transformer\EnumNormalizer;
use Ufo\RpcObject\Tests\Fixtures\{Status, Priority, Direction};

class EnumNormalizerTest extends TestCase
{
    /**
     * Перевіряє нормалізацію рядкових, числових і звичайних enum та відновлення початкового варіанта зі
     * значення.
     */
    #[DataProvider('enumValues')]
    public function testEnumRoundTrip(\UnitEnum $case, string|int $value): void
    {
        $normalizer = new EnumNormalizer();
        self::assertTrue($normalizer->supportsNormalization($case));
        self::assertSame($value, $normalizer->normalize($case));
        self::assertTrue($normalizer->supportsDenormalization($value, $case::class));
        self::assertSame($case, $normalizer->denormalize($value, $case::class));
    }

    public static function enumValues(): iterable
    {
        yield [Status::Active, 'active']; yield [Status::Inactive, 'inactive'];
        yield [Priority::Low, 1]; yield [Priority::High, 2];
        yield [Direction::North, 'North']; yield [Direction::South, 'South'];
    }

    /**
     * Перевіряє визначення підтримуваних даних, збереження скалярів і відновлення enum з числового рядка
     * або імені в іншому регістрі.
     */
    public function testSupportsAndScalarPassthrough(): void
    {
        $normalizer = new EnumNormalizer();
        self::assertFalse($normalizer->supportsNormalization('active'));
        self::assertFalse($normalizer->supportsDenormalization([], Status::class));
        self::assertSame(5, $normalizer->normalize(5));
        self::assertSame([\UnitEnum::class => true, \BackedEnum::class => true], $normalizer->getSupportedTypes('json'));
        self::assertSame(Priority::High, $normalizer->denormalize('2', Priority::class));
        self::assertSame(Direction::North, $normalizer->denormalize('north', Direction::class));
    }

    /**
     * Перевіряє перетворення помилки невідомого значення enum на виняток серіалізатора.
     */
    public function testInvalidEnumValueBecomesSerializerException(): void
    {
        $this->expectException(UnexpectedValueException::class);
        (new EnumNormalizer())->denormalize('missing', Status::class);
    }
}
