<?php

namespace Ufo\RpcObject\Tests\RPC;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\Constraints as Assert;
use Ufo\RpcError\RpcInternalException;
use Ufo\RpcObject\RPC\{Assertions, AssertionsCollection, Cache, CacheRelation, DTO, DTOCollection, Info, Param};
use Ufo\RpcObject\Tests\Fixtures\{Person, Procedure};
use Ufo\RpcObject\Transformer\AttributeHelper;

class AttributesTest extends TestCase
{
    /**
     * Перевіряє визначення одиночних і складених типів параметра за його бітовою маскою.
     */
    #[DataProvider('types')]
    public function testParamTypeMasks(int $mask, string|array $expected): void
    {
        self::assertSame($expected, (new Param($mask))->getType());
    }

    public static function types(): iterable
    {
        yield [Param::STRING, 'string'];
        yield [Param::INT, 'int'];
        yield [Param::FLOAT, 'float'];
        yield [Param::NULL, 'null'];
        yield [Param::NUMBER, ['float', 'int']];
        yield [Param::NULLABLE_STRING, ['string', 'null']];
        yield [Param::MIXED, ['string', 'float', 'int', 'null']];
    }

    /**
     * Перевіряє відхилення масок параметра, що містять недозволені біти.
     */
    #[DataProvider('invalidMasks')]
    public function testUnknownBitsAreRejected(int $mask): void
    {
        $this->expectException(\InvalidArgumentException::class);
        new Param($mask);
    }

    public static function invalidMasks(): iterable { yield [16]; yield [-1]; yield [32 | Param::STRING]; }

    /**
     * Перевіряє пріоритет ознаки колекції над контекстом, читання контекстних значень і перетворення назв
     * типів на бітові маски.
     */
    public function testParamContextAndBitMapping(): void
    {
        $param = new Param(Param::STRING, true, ['collection' => false, 'default' => 'fallback']);
        self::assertTrue($param->isCollection());
        self::assertSame('fallback', $param->getData('default'));
        self::assertNull($param->getData('missing'));
        self::assertFalse((new Param(Param::INT))->isCollection());
        foreach (['string' => Param::STRING, 'int' => Param::INT, 'float' => Param::FLOAT, 'null' => Param::NULL, 'unknown' => Param::NULL] as $type => $mask) {
            self::assertSame($mask, Param::bitFromType($type));
        }
    }

    /**
     * Перевіряє формування версії API та повних імен вилучених методів з очищенням пробілів і видаленням
     * дублікатів.
     */
    public function testInfoPrefixesTrimsAndDeduplicatesRemovedMethods(): void
    {
        $info = new Info('users', ':', 3, [' find ', 'find', 'save']);
        self::assertSame('users', $info->alias);
        self::assertSame('v3', $info->version);
        self::assertSame(['users:find', 'users:save'], $info->removedMethods);
        self::assertSame('v1', (new Info('users'))->version);
        self::assertSame([], (new Info('users'))->removedMethods);
    }

    /**
     * Перевіряє стандартні й задані параметри кешу та копіювання зв’язку кешу для іншого сервісу без зміни
     * оригіналу.
     */
    public function testCacheDefaultsAndRelationClone(): void
    {
        $cache = new Cache();
        self::assertSame(60, $cache->lifetimeSecond);
        self::assertSame(['prod'], $cache->environments);
        $custom = new Cache(Cache::T_HOUR, [Cache::ENV_TEST]);
        self::assertSame(3600, $custom->lifetimeSecond);
        self::assertSame(['test'], $custom->environments);
        $relation = new CacheRelation('OldService', ['find'], true);
        $copy = $relation->cloneToClass('NewService');
        self::assertNotSame($relation, $copy);
        self::assertSame('OldService', $relation->serviceFQCN);
        self::assertSame('NewService', $copy->serviceFQCN);
        self::assertSame(['find'], $copy->methods);
        self::assertTrue($copy->warmUp);
    }

    /**
     * Перевіряє метадані DTO і колекції DTO та виняток при читанні формату, який ще не було заповнено.
     */
    #[DataProvider('dtoAttributes')]
    public function testDtoMetadataAndUnparsedFormat(string $class, bool $collection): void
    {
        $dto = $class === DTO::class
            ? new DTO(Person::class, context: ['namespaces' => ['Ufo\\RpcObject\\Tests\\Fixtures']])
            : new DTOCollection(Person::class, ['namespaces' => ['Ufo\\RpcObject\\Tests\\Fixtures']])
        ;
        self::assertSame(Person::class, $dto->dtoFQCN);
        self::assertSame($collection, $dto->isCollection());
        self::assertNull($dto->getRealFormat('name'));
        self::assertSame(['Ufo\\RpcObject\\Tests\\Fixtures'], $dto->namespaces());
        $this->expectException(RpcInternalException::class);
        $this->expectExceptionMessage('is not parsed');
        $dto->getFormat();
    }

    public static function dtoAttributes(): iterable
    {
        yield [DTO::class, false];
        yield [DTOCollection::class, true];
    }

    /**
     * Перевіряє серіалізацію обмежень валідації й тексту їх конструктора та збереження цих даних у
     * колекції.
     */
    public function testAssertionsSerializeConstraintAndConstructor(): void
    {
        $assertions = new Assertions([new Assert\NotBlank()], 'new Assert\\NotBlank()');
        $data = $assertions->toArray();
        self::assertSame('new Assert\\NotBlank()', $data['constructor']);
        self::assertCount(1, $data['payload']);
        self::assertArrayHasKey('message', $data['payload'][0]);
        $collection = new AssertionsCollection();
        self::assertSame($collection, $collection->addAssertions('name', $assertions));
        self::assertSame(['name' => $assertions], $collection->getAssertionsCollection());
        self::assertSame(['name' => $data], $collection->toArray());
    }

    /**
     * Перевіряє отримання обмежень саме потрібного параметра через reflection, підтримку псевдоніма RPC та
     * пропуск параметрів без атрибута.
     */
    public function testReflectionReadsAssertionsForOnlyTheRequestedParameter(): void
    {
        $method = new \ReflectionMethod(Procedure::class, 'save');
        $collection = new AssertionsCollection();
        foreach ($method->getParameters() as $param) $collection->fillAssertion(Procedure::class, $method, $param);
        self::assertSame(['name', 'age'], array_keys($collection->getAssertionsCollection()));
        self::assertStringContainsString('NotBlank', $collection->toArray()['name']['constructor']);
        self::assertStringNotContainsString('Positive', $collection->toArray()['name']['constructor']);
        self::assertStringContainsString('Positive', $collection->toArray()['age']['constructor']);
        self::assertNull(AttributeHelper::getMethodArgumentAttributesAsString(Procedure::class, 'save', 'unchecked'));
        self::assertSame('new Assert\\NotBlank()', AttributeHelper::getMethodArgumentAttributesAsString(Procedure::class, 'aliased', 'name'));
    }
}
