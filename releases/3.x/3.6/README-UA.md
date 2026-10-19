[English](README.md)

# 3.6 — dto-transformer 3, `DTOTransformerBundleBootTrait`

Версії: 3.6.0, 3.6.1. Перший реліз: 2026-05-15.

## Головне

- Потрібен `ufo-tech/dto-transformer` ^3.0 (було ^2.0).
- Новий `DTOTransformerBundleBootTrait`: ініціалізує `DTOTransformer` і `ServiceTransformer` з контейнера Symfony з постійним кешем.

## Що нового

### `DTOTransformerBundleBootTrait`

`Ufo\RpcObject\Transformer\DTOTransformerBundleBootTrait` призначений для класу бандла. `bootDTOTransformers()` створює (або бере з контейнера) трансформери й викликає `DTOTransformer::boot()` / `ServiceTransformer::boot()`:

1. `DefaultDTOTransformerFactory` з контейнера, якщо зареєстрований; інакше `DefaultDTOTransformerFactory::default()` з постійним кешем.
2. Кеш — перший сервіс `Symfony\Contracts\Cache\CacheInterface`, знайдений у `CACHE_SERVICES`: `ufo.dto.cache`, `rpc.file.cache`, `rpc.cache.pool`, `cache.system`, `cache.app`.
3. Сервіс трансформера, зареєстрований у контейнері під іменем класу, має пріоритет над фабрикою.

`RuntimeException` з `boot()` ігнорується (уже ініціалізований трансформер лишається як є).

**Як використати**

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

## Зміни та виправлення

- 3.6.0: `EnumNormalizer` денормалізує через `Ufo\DTO\Transformer\Converter\EnumConverter::toEnum()` (API dto-transformer 3) замість `DTOTransformer::transformEnum()`.
- 3.6.0: `RpcRequest::fromArray()` — виправлено пріоритет операторів: `(string)($data['method'] ?? '')`; відсутній `method` більше не дає попередження про невизначений ключ.
- 3.6.1: `RequestRules` створює `Assert\Collection` і `Assert\Count` з іменованими аргументами замість масивів опцій (Symfony 7.3+ позначає форму з масивом застарілою).

## Примітки щодо оновлення

1. Оновіть `ufo-tech/dto-transformer` до ^3.0. json-rpc-bundle вимагає `rpc-objects` ^3.6 починаючи з 10.0.2.
2. Якщо ваш код викликає `DTOTransformer::transformEnum()`, перейдіть на `EnumConverter::toEnum()`.
