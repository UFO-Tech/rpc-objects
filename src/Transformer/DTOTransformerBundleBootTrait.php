<?php

declare(strict_types = 1);

namespace Ufo\RpcObject\Transformer;

use Psr\Container\ContainerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Ufo\DTO\DTOTransformer;
use Ufo\DTO\Factory\DefaultDTOTransformerFactory;
use Ufo\DTO\ServiceTransformer;

trait DTOTransformerBundleBootTrait
{
    protected const array CACHE_SERVICES = [
        'ufo.dto.cache',
        'rpc.file.cache',
        'rpc.cache.pool',
        'cache.system',
        'cache.app',
    ];

    protected function bootDTOTransformers(): void
    {
        $factory = $this->resolveDTOTransformerFactory();

        $this->bootDTOTransformer(DTOTransformer::class, $factory);
        $this->bootDTOTransformer(ServiceTransformer::class, $factory);
    }

    abstract protected function getContainer(): ContainerInterface;

    protected function resolveDTOTransformerFactory(): DefaultDTOTransformerFactory
    {
        $container = $this->getContainer();
        if ($container->has(DefaultDTOTransformerFactory::class)) {
            $factory = $container->get(DefaultDTOTransformerFactory::class);

            if ($factory instanceof DefaultDTOTransformerFactory) {
                return $factory;
            }
        }

        return DefaultDTOTransformerFactory::default(
            persistentCache: $this->resolveDTOTransformerPersistentCache(),
        );
    }

    protected function resolveDTOTransformerPersistentCache(): ?CacheInterface
    {
        $container = $this->getContainer();
        foreach (static::CACHE_SERVICES as $id) {
            if (!$container->has($id)) continue;
            $cache = $container->get($id);

            if ($cache instanceof CacheInterface) {
                return $cache;
            }
        }

        return null;
    }

    /**
     * @param class-string<DTOTransformer> $transformerClass
     */
    protected function bootDTOTransformer(
        string $transformerClass,
        DefaultDTOTransformerFactory $factory,
    ): void
    {
        try {
            $transformerClass::boot(
                $this->resolveDTOTransformer($transformerClass, $factory),
            );
        } catch (\RuntimeException) {
        }
    }

    /**
     * @param class-string<DTOTransformer> $transformerClass
     */
    protected function resolveDTOTransformer(
        string $transformerClass,
        DefaultDTOTransformerFactory $factory,
    ): DTOTransformer
    {
        $container = $this->getContainer();
        if (
            $container->has($transformerClass)
            && is_a($transformerClass, DTOTransformer::class, true)
        ) {
            $transformer = $container->get($transformerClass);

            if ($transformer instanceof $transformerClass) {
                return $transformer;
            }
        }

        return $factory->create($transformerClass);
    }
}