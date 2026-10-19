[Українська](README-UA.md)

# 3.6 — dto-transformer 3, `DTOTransformerBundleBootTrait`

Versions: 3.6.0, 3.6.1. First released: 2026-05-15.

## Highlights

- Requires `ufo-tech/dto-transformer` ^3.0 (was ^2.0).
- New `DTOTransformerBundleBootTrait`: boots `DTOTransformer` and `ServiceTransformer` from the Symfony container with a persistent cache.

## What's new

### `DTOTransformerBundleBootTrait`

`Ufo\RpcObject\Transformer\DTOTransformerBundleBootTrait` is meant for a bundle class. `bootDTOTransformers()` creates (or takes from the container) the transformers and calls `DTOTransformer::boot()` / `ServiceTransformer::boot()`:

1. `DefaultDTOTransformerFactory` from the container, if registered; otherwise `DefaultDTOTransformerFactory::default()` with a persistent cache.
2. The cache is the first `Symfony\Contracts\Cache\CacheInterface` service found in `CACHE_SERVICES`: `ufo.dto.cache`, `rpc.file.cache`, `rpc.cache.pool`, `cache.system`, `cache.app`.
3. A transformer service registered in the container under its class name wins over the factory.

A `RuntimeException` from `boot()` is ignored (a transformer that is already booted stays as it is).

**How to use**

```php
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Ufo\RpcObject\Transformer\DTOTransformerBundleBootTrait;

final class AcmeRpcBundle extends Bundle
{
    use DTOTransformerBundleBootTrait;

    public function boot(): void
    {
        $this->bootDTOTransformers();
    }

    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }
}
```

## Changes and fixes

- 3.6.0: `EnumNormalizer` denormalizes through `Ufo\DTO\Transformer\Converter\EnumConverter::toEnum()` (dto-transformer 3 API) instead of `DTOTransformer::transformEnum()`.
- 3.6.0: `RpcRequest::fromArray()` — operator precedence fix: `(string)($data['method'] ?? '')`; a missing `method` no longer triggers an undefined-key warning.
- 3.6.1: `RequestRules` builds `Assert\Collection` and `Assert\Count` with named arguments instead of option arrays (Symfony 7.3+ deprecates the array form).

## Upgrade notes

1. Update `ufo-tech/dto-transformer` to ^3.0. json-rpc-bundle requires `rpc-objects` ^3.6 from 10.0.2.
2. If your code calls `DTOTransformer::transformEnum()`, switch to `EnumConverter::toEnum()`.
