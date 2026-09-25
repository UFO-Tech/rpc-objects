<?php

namespace Ufo\RpcObject\Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerInterface;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\ServiceTransformer;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;
use Ufo\RpcObject\Transformer\DTOTransformerBundleBootTrait;
use Ufo\RpcObject\Tests\Fixtures\Person;

class TestBundle
{
    use DTOTransformerBundleBootTrait {
        bootDTOTransformers as public boot;
        resolveDTOTransformerFactory as public factory;
        resolveDTOTransformerPersistentCache as public cache;
        resolveDTOTransformer as public transformer;
    }

    public function __construct(private ContainerInterface $container) {}
    protected function getContainer(): ContainerInterface { return $this->container; }
}

class BundleBootTest extends TestCase
{
    protected function tearDown(): void
    {
        DTOTransformer::reset();
        ServiceTransformer::reset();
        DTOTransformer::boot(DefaultDTOTransformerFactory::default()->create());
    }

    private function bundle(array $services): TestBundle
    {
        $container = $this->createStub(ContainerInterface::class);
        $container->method('has')->willReturnCallback(fn (string $id): bool => array_key_exists($id, $services));
        $container->method('get')->willReturnCallback(fn (string $id): mixed => $services[$id]);
        return new TestBundle($container);
    }

    /**
     * Перевіряє ініціалізацію обох DTO-перетворювачів, безпечний повторний запуск і можливість створення
     * DTO після запуску.
     */
    public function testBootInitializesBothTransformersAndCanBeRepeated(): void
    {
        DTOTransformer::reset();
        ServiceTransformer::reset();
        $bundle = $this->bundle([]);
        self::assertNull($bundle->cache());
        $bundle->boot();
        $bundle->boot();
        self::assertTrue(DTOTransformer::isInitialized());
        self::assertTrue(ServiceTransformer::isInitialized());
        self::assertEquals(new Person('Ada'), DTOTransformer::fromArray(Person::class, ['name' => 'Ada']));
        self::assertEquals(new Person('Grace'), ServiceTransformer::fromArray(Person::class, ['name' => 'Grace']));
    }

    /**
     * Перевіряє пріоритет фабрики та DTO-перетворювача, явно зареєстрованих у контейнері.
     */
    public function testContainerFactoryAndTransformerArePreferred(): void
    {
        $factory = DefaultDTOTransformerFactory::default();
        $transformer = $factory->create();
        $bundle = $this->bundle([DefaultDTOTransformerFactory::class => $factory, DTOTransformer::class => $transformer]);
        self::assertSame($factory, $bundle->factory());
        self::assertSame($transformer, $bundle->transformer(DTOTransformer::class, $factory));
    }

    /**
     * Перевіряє використання стандартних фабрики й перетворювача, якщо відповідні сервіси контейнера мають
     * неправильні типи.
     */
    public function testInvalidContainerServicesFallBackToDefaults(): void
    {
        $bundle = $this->bundle([DefaultDTOTransformerFactory::class => new \stdClass(), DTOTransformer::class => new \stdClass()]);
        $factory = $bundle->factory();
        self::assertInstanceOf(DefaultDTOTransformerFactory::class, $factory);
        self::assertInstanceOf(DTOTransformer::class, $bundle->transformer(DTOTransformer::class, $factory));
    }

    /**
     * Перевіряє вибір кешу за пріоритетом сервісів, пропуск сервісів неправильного типу та використання
     * резервного кешу.
     */
    public function testCacheResolutionUsesPriorityAndSkipsWrongTypes(): void
    {
        $preferred = new ArrayAdapter();
        $fallback = new ArrayAdapter();
        $bundle = $this->bundle(['ufo.dto.cache' => new \stdClass(), 'rpc.file.cache' => $preferred, 'cache.app' => $fallback]);
        self::assertSame($preferred, $bundle->cache());
        self::assertInstanceOf(DefaultDTOTransformerFactory::class, $bundle->factory());
        self::assertSame($fallback, $this->bundle(['cache.app' => $fallback])->cache());
    }
}
