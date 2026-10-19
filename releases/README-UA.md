[English](README.md)

# Опис релізів

Що додано в кожній версії і як цим користуватися. Для кожної мінорної версії є окрема сторінка, для кожної мажорної — огляд із примітками до оновлення: [3.x](3.x/README-UA.md) · [2.x](2.x/README-UA.md) · [1.x](1.x/README-UA.md).

Більшість історичних git-тегів не збереглася. Версії відновлено з поля `version` у `composer.json` (було до 3.7.0) і тегів, що лишилися; «Реліз» — дата першого коміту мінорної версії.

| Версія | Реліз | Головне |
| --- | --- | --- |
| [3.9](3.x/3.9/README-UA.md) | 2026-10-14 | 3.9.2: незмінний `RpcResponseContextBuilder`. 3.9.1: constraint-и валідатора кешуються для кожного методу, помилки валідації більше не ковтаються. `#[RPC\APIGroups]` і `RpcAsyncRequest::$apiGroup` |
| [3.8](3.x/3.8/README-UA.md) | 2026-09-25 | JSON-RPC notifications (`RpcNotificationRequest`), `RpcRequestFactory`, Symfony 8, валідація `$rpc` всередині `params.$rpc` |
| [3.7](3.x/3.7/README-UA.md) | 2026-09-17 | Readonly `RpcAsyncRequest` з `meta`, `RpcTransport::SYNC_PREFIX` / `ASYNC_PREFIX`, `value_type` як ім'я класу |
| [3.6](3.x/3.6/README-UA.md) | 2026-05-15 | dto-transformer ^3, `DTOTransformerBundleBootTrait` (трансформери з контейнера з постійним кешем) |
| [3.5](3.x/3.5/README-UA.md) | 2026-02-22 | Версії API: `#[RPC\Info(version:, removedMethods:)]`, `RpcRequest::$apiVersion`; видалено `ResultAsDTO` і застарілі аргументи DTO/Param |
| [3.4](3.x/3.4/README-UA.md) | 2025-10-24 | `$rpc.ignoreCache`, `RpcResponse::toArray(ContextBuilderInterface)`, вхідний `rayId` зберігається |
| [3.3](3.x/3.3/README-UA.md) | 2025-09-30 | `EnumNormalizer` і `DtoObjectNormalizer`, `Param(collection:)`, `#[RPC\ResultAsDTO]` застарів, `$className` лише з `AS_SMART` |
| [3.2](3.x/3.2/README-UA.md) | 2025-09-29 | dto-transformer ^2, `#[RPC\DTOCollection]`, опції `#[RPC\DTO]` / `#[RPC\Param]` через `context` |
| [3.1](3.x/3.1/README-UA.md) | 2025-06-30 | `ConstraintsImposedException` переїхав у rpc-exceptions, `RpcResponse::getResult(): mixed` |
| [3.0](3.x/3.0/README-UA.md) | 2025-05-10 | DTO-шар винесено в `ufo-tech/dto-transformer` (`Ufo\DTO\…`), `#[RPC\Param]`, `#[RPC\CacheRelation]`, `AttributeTransformer`; видалено `#[RPC\Response]` і `Ufo\RpcObject\Events` |
| [2.9](2.x/2.9/README-UA.md) | 2025-05-09 | `renameKeys` у `#[RPC\DTO]`, DTO-атрибути на параметрах конструктора, `fromArray(..., $renameKey): static` |
| [2.8](2.x/2.8/README-UA.md) | 2025-03-13 | `#[RPC\Lock]`, спецпараметри одним об'єктом `$rpc` з `rayId`, валідація DTO через `#[RPC\Assertions]`, вкладені DTO через `#[RPC\DTO]`, Symfony 7.2 |
| [2.7](2.x/2.7/README-UA.md) | 2025-01-13 | `DTOTransformer` (DTO ↔ масив) з трейтами `IArrayConvertible` / `IArrayConstructible`, callback URL мусить мати TLD, потрібен `ext-intl` |
| [2.6](2.x/2.6/README-UA.md) | 2024-07-24 | `#[RPC\IgnoreApi]`, типи з PHPDoc і конструктора та денормалізація `array<string, Dto>`, перевірка й кешування callback URL |
| [2.5](2.x/2.5/README-UA.md) | 2024-07-14 | `#[RPC\ResultAsDTO]` для схем результату, `RpcResponseContextBuilder` / `RpcErrorNormalizer` у `Ufo\RpcObject\Transformer` |
| [2.4](2.x/2.4/README-UA.md) | 2024-07-08 | Класи подій для json-rpc-bundle 7, `RpcError::fromThrowable()`, `TypeHintResolver` |
| [2.3](2.x/2.3/README-UA.md) | 2024-07-05 | `@FROM:<id>()` передає весь результат батьківського запиту, `RpcTransport::getDomainUrl()` |
| [2.2](2.x/2.2/README-UA.md) | 2024-07-02 | Групи серіалізації для результатів (`list`, `detail`, `group`…), нормалізація UUID |
| [2.1](2.x/2.1/README-UA.md) | 2024-05-16 | `RpcAsyncRequest`, DSN `RpcTransport` з маскуванням `#[Secret]`, обмеження Symfony як `{class, context}`, PHP 8.3 |
| [2.0](2.x/2.0/README-UA.md) | 2024-05-10 | `#[RPC\Cache]`, `RpcValidator` з помилками по параметрах, атрибути PHP замість анотацій, Symfony validator 7 |
| [1.5](1.x/1.5/README-UA.md) | 2024-04-19 | Атрибути перенесено в `RPC\*` (`RPC\Info` аліас, `RPC\Response`, `RPC\Assertions`), нормалізація дат |
| [1.4](1.x/1.4/README-UA.md) | 2023-02-01 | Початкова публічна версія: модель запиту/відповіді JSON-RPC 2.0, batch з `@FROM:<id>(<field>)`, `$rpc.callback` / `$rpc.timeout`, `throwError()` |
