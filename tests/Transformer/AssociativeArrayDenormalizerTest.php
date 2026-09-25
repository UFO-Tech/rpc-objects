<?php

namespace Ufo\RpcObject\Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Exception\InvalidArgumentException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use Ufo\RpcObject\Transformer\AssociativeArrayDenormalizer;
use Ufo\RpcObject\Tests\Fixtures\Person;

class AssociativeArrayDenormalizerTest extends TestCase
{
    /**
     * Перевіряє визначення асоціативного масиву, збереження ключів і скалярів та делегування перетворення
     * вкладених масивів із заданим типом.
     */
    public function testPreservesKeysAndScalarsWhileConvertingNestedValues(): void
    {
        $inner = $this->createMock(DenormalizerInterface::class);
        $person = new Person('Ada');
        $context = ['value_type' => Person::class];
        $inner->expects(self::once())->method('denormalize')->with(['name' => 'Ada'], Person::class, 'json', $context)->willReturn($person);
        $normalizer = new AssociativeArrayDenormalizer($inner);
        self::assertTrue($normalizer->supportsDenormalization(['key' => []], 'array'));
        self::assertFalse($normalizer->supportsDenormalization([1, 2], 'array'));
        self::assertFalse($normalizer->supportsDenormalization('text', 'array'));
        self::assertSame(['person' => $person, 'count' => 1], $normalizer->denormalize(['person' => ['name' => 'Ada'], 'count' => 1], 'array', 'json', $context));
        self::assertSame(['*' => true], $normalizer->getSupportedTypes(null));
    }

    /**
     * Перевіряє отримання класу вкладених значень з об’єкта типу, який надає метод getClassName().
     */
    public function testTypeObjectIsAccepted(): void
    {
        $type = new class { public function getClassName(): string { return Person::class; } };
        $inner = $this->createMock(DenormalizerInterface::class);
        $person = new Person('Ada');
        $inner->expects(self::once())->method('denormalize')->with(['name' => 'Ada'], Person::class, null, ['value_type' => $type])->willReturn($person);
        self::assertSame(['key' => $person], (new AssociativeArrayDenormalizer($inner))->denormalize(['key' => ['name' => 'Ada']], 'array', context: ['value_type' => $type]));
    }

    /**
     * Перевіряє збереження вкладених і порожніх масивів без виклику внутрішнього перетворювача, якщо тип
     * значень не задано.
     */
    public function testWithoutValueTypeDataIsUntouched(): void
    {
        $inner = $this->createMock(DenormalizerInterface::class);
        $inner->expects(self::never())->method('denormalize');
        $normalizer = new AssociativeArrayDenormalizer($inner);
        self::assertSame(['key' => ['a' => 1]], $normalizer->denormalize(['key' => ['a' => 1]], 'array'));
        self::assertSame([], $normalizer->denormalize([], 'array'));
    }

    /**
     * Перевіряє відхилення даних, які не є масивом, під час денормалізації асоціативного масиву.
     */
    public function testNonArrayInputRejected(): void
    {
        $normalizer = new AssociativeArrayDenormalizer($this->createStub(DenormalizerInterface::class));
        $this->expectException(InvalidArgumentException::class);
        $normalizer->denormalize('invalid', 'array');
    }

    /**
     * Перевіряє відхилення некоректного опису типу вкладених значень у контексті денормалізації.
     */
    public function testInvalidValueTypeRejected(): void
    {
        $normalizer = new AssociativeArrayDenormalizer($this->createStub(DenormalizerInterface::class));
        $this->expectException(InvalidArgumentException::class);
        $normalizer->denormalize(['key' => []], 'array', context: ['value_type' => 42]);
    }
}
