[English](README.md)

# ufo-tech/rpc-objects 3.x

Мажорна версія 3 винесла DTO-шар в окремий пакет `ufo-tech/dto-transformer` і прибрала класи подій — вони тепер у json-rpc-bundle 8+. Тут лишилися об'єктна модель JSON-RPC, RPC-атрибути та налаштування серіалізатора. Протягом лінійки 3.x пакет отримав:
- `#[RPC\Param]`, `#[RPC\CacheRelation]`, `#[RPC\DTOCollection]` і `AttributeTransformer`
- `EnumNormalizer` / `DtoObjectNormalizer` і перероблений конвеєр серіалізації
- `$rpc.ignoreCache` і `RpcResponse::toArray(ContextBuilderInterface)`
- версії API (`#[RPC\Info(version:, removedMethods:)]`, `RpcRequest::$apiVersion`)
- `DTOTransformerBundleBootTrait` для ініціалізації трансформерів з контейнера
- JSON-RPC notifications (`RpcNotificationRequest`) і `RpcRequestFactory`
- підтримку Symfony 8
- групи API (`#[RPC\APIGroups]`)

## Вимоги

| | 3.0.1 | 3.9.2 |
|---|---|---|
| PHP | `>=8.3` | `>=8.3` |
| `symfony/serializer`, `symfony/validator` | `^7.2` | `^7.2\|^8.0` (з 3.8) |
| `symfony/property-info` | — | `^7.2\|^8.0` |
| `symfony/http-client`, `symfony/property-access` | `*` | `*` |
| `ufo-tech/rpc-exceptions` | `*` | `^2.0.1` (з 3.8) |
| `ufo-tech/dto-transformer` | `^1.0` | `^3.1` (`^2.0` у 3.2–3.5, `^3.0` з 3.6) |
| Інше | `ext-intl` | `ext-intl` |

## Версії

| Версія | Головне |
|---|---|
| [3.0](3.0/README-UA.md) | DTO-шар винесено в `ufo-tech/dto-transformer` (`Ufo\DTO\…`), `#[RPC\Param]`, `#[RPC\CacheRelation]`, `AttributeTransformer`; видалено `#[RPC\Response]` і `Ufo\RpcObject\Events` |
| [3.1](3.1/README-UA.md) | `ConstraintsImposedException` переїхав у rpc-exceptions, `RpcResponse::getResult(): mixed`, тимчасовий `DocsHelper` (додано в 3.1.0, видалено в 3.1.2) |
| [3.2](3.2/README-UA.md) | dto-transformer ^2, `#[RPC\DTOCollection]`, опції `#[RPC\DTO]` / `#[RPC\Param]` через `context` |
| [3.3](3.3/README-UA.md) | `EnumNormalizer` і `DtoObjectNormalizer`, `Param(collection:)`, `#[RPC\ResultAsDTO]` застарів, `$className` лише з `AS_SMART` |
| [3.4](3.4/README-UA.md) | `$rpc.ignoreCache`, `RpcResponse::toArray(ContextBuilderInterface)`, вхідний `rayId` зберігається |
| [3.5](3.5/README-UA.md) | Версії API: `#[RPC\Info(version:, removedMethods:)]`, `RpcRequest::$apiVersion`; видалено `ResultAsDTO` і застарілі аргументи DTO/Param |
| [3.6](3.6/README-UA.md) | dto-transformer ^3, `DTOTransformerBundleBootTrait` (трансформери з контейнера з постійним кешем) |
| [3.7](3.7/README-UA.md) | Readonly `RpcAsyncRequest` з `meta`, `RpcTransport::SYNC_PREFIX` / `ASYNC_PREFIX`, `value_type` як ім'я класу |
| [3.8](3.8/README-UA.md) | JSON-RPC notifications (`RpcNotificationRequest`), `RpcRequestFactory`, Symfony 8, валідація `$rpc` всередині `params.$rpc` |
| [3.9](3.9/README-UA.md) | `#[RPC\APIGroups]` і `RpcAsyncRequest::$apiGroup`; 3.9.1 кешує constraint-и валідатора й не ковтає помилки; 3.9.2 незмінний `RpcResponseContextBuilder` |

## Оновлення з 2.x

1. **Додайте `ufo-tech/dto-transformer`** (`^1.0` для 3.0) і оновіть імпорти:

   | Було | Стало |
   | --- | --- |
   | `Ufo\RpcObject\DTO\DTOTransformer` | `Ufo\DTO\DTOTransformer` |
   | `Ufo\RpcObject\DTO\IArrayConstructible`, `IArrayConvertible` | `Ufo\DTO\Interfaces\…` |
   | `Ufo\RpcObject\DTO\ArrayConstructibleTrait`, `ArrayConvertibleTrait` | `Ufo\DTO\…` |
   | `Ufo\RpcObject\Helpers\TypeHintResolver` | `Ufo\DTO\Helpers\TypeHintResolver` |

   `DTOAttributesEnum` видалено.
2. **`#[RPC\Response]` видалено.** Використовуйте `#[RPC\ResultAsDTO]` (його самого видалено в 3.5 — див. нижче).
3. **`Ufo\RpcObject\Events\*` видалено** (`RpcEvent`, `RpcPreExecuteEvent`, `RpcErrorEvent`, …). json-rpc-bundle 8 має власні події.
4. **`RpcBatchRequest::addResult(array)`** → `addResponse(RpcResponse, array)`.
5. **`#[RPC\ResultAsDTO]`** ставиться лише на методи; на властивостях і параметрах — `#[RPC\DTO]`.
6. **`Rules\Validator\Validator`** розширює `Ufo\DTO\Helpers\Validator`; з власного API лишився тільки `throw()`.
7. **Поведінка:** `RpcRequest` сортує params через `ksort`; `getSpecialParams()` завжди містить `rayId` (за замовчуванням — id запиту); у `RpcResponse::getResult()` з'явився прапорець `$asIs`.

## Зміни, що ламають сумісність, усередині 3.x

Ця лінійка кілька разів ламала сумісність у мінорних і patch-релізах. Перевірте їх під час переходу:
- **3.1.1:** `Ufo\RpcObject\Rules\Validator\ConstraintsImposedException` видалено → `Ufo\RpcError\ConstraintsImposedException`. **3.1.2:** видалено `DocsHelper`.
- **3.2:** `AttributeTransformer::fromArray()` отримав аргумент `$namespaces`.
- **3.3:** у `Param` з'явився новий другий позиційний аргумент `collection` — позиційні виклики з `default` ламаються. У 3.3.3 `AS_SIMPLE_ARRAY` перейменовано на `AS_SMART`.
- **3.4:** `SpecialRpcParams` став `readonly`; `IRpcSpecialParamHandler` отримав `setParam()` / `resetParams()`; вхідний `rayId` має пріоритет над id запиту.
- **3.5:** видалено `ResultAsDTO`; `DTO` втратив `renameKeys` / `transformerFQCN`, `Param` — `default` / `convertorFQCN`; `RPC\Info::$alias` обов'язковий.
- **3.7:** `RpcAsyncRequest` став `readonly`; `getRpcRequest()` застарів.
- **3.8:** `RpcRequest::S_GROUP` змінився з `'raw'` на ім'я класу; id `0` більше не перегенеровується; запит без `id` — це notification; `params: null`, `timeout: "30"` і `callback: null` відхиляються.
- **3.9.1:** змінилася сигнатура `RpcValidator::validateParam()`; неправильно налаштований `#[RPC\Assertions]` кидає виключення замість тихого пропуску валідації.
- **3.9.2:** `RpcResponseContextBuilder` незмінний — використовуйте значення, яке повертають `with*()` / `removeParent()`. Див. [3.9](3.9/README-UA.md).
