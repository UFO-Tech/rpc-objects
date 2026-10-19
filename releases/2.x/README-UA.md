[English](README.md)

# ufo-tech/rpc-objects 2.x

Мажорна версія 2 замінила анотації Doctrine нативними атрибутами PHP і перейшла на Symfony Validator 7. Більшість метаданих, які сервер читає сьогодні, з'явилася саме в цій лінійці. Протягом 2.x пакет отримав:
- `#[RPC\Cache]`, `#[RPC\IgnoreApi]`, `#[RPC\Lock]`, `#[RPC\ResultAsDTO]` і `#[RPC\DTO]`
- `RpcValidator` з помилками по кожному параметру та експорт Symfony constraints як `{class, context}`
- `RpcAsyncRequest` і DSN `RpcTransport` з маскуванням `#[Secret]`
- групи серіалізації для результатів і `RpcResponseContextBuilder` / `RpcErrorNormalizer` у `Ufo\RpcObject\Transformer`
- класи подій для json-rpc-bundle 7 і `RpcError::fromThrowable()`
- `DTOTransformer` (DTO ↔ масив) з валідацією DTO через `#[RPC\Assertions]`
- спеціальні параметри одним об'єктом `$rpc` з `rayId`

## Вимоги

| | 2.0.1 | 2.9.0 |
|---|---|---|
| PHP | `>=8.2` | `>=8.3` (з 2.1.0) |
| Symfony | `validator ^7.0`, інші `symfony/*` без обмежень | `serializer ^7.2`, `validator ^7.2` (з 2.8.6), інші `symfony/*` без обмежень |
| Інше | `ufo-tech/rpc-exceptions *` | додатково `ext-intl` (з 2.7.3) |

Не оголошені в `require`, але використовуються кодом: `symfony/event-dispatcher-contracts` (події, 2.4–2.9), `symfony/http-foundation` (`RealUrlValidator`, з 2.6.4), `symfony/lock` (`RPC\Lock`, з 2.8.0). До 2.5 `RpcResponse` також посилався на класи з `ufo-tech/json-rpc-bundle`.

## Версії

| Версія | Головне |
|---|---|
| [2.0](2.0/README-UA.md) | `#[RPC\Cache]`, `RpcValidator` з помилками по параметрах, атрибути PHP замість анотацій, Symfony validator 7 |
| [2.1](2.1/README-UA.md) | `RpcAsyncRequest`, DSN `RpcTransport` з маскуванням `#[Secret]`, обмеження Symfony як `{class, context}`, PHP 8.3 |
| [2.2](2.2/README-UA.md) | Групи серіалізації для результатів (`list`, `detail`, `group`…), вихідний текст обмежень у `toArray()`, нормалізація UUID |
| [2.3](2.3/README-UA.md) | `@FROM:<id>()` передає весь результат батьківського запиту, `RpcTransport::getDomainUrl()` |
| [2.4](2.4/README-UA.md) | Класи подій для json-rpc-bundle 7 (`rpc.request` … `rpc.error`), `RpcError::fromThrowable()`, `TypeHintResolver` |
| [2.5](2.5/README-UA.md) | `#[RPC\ResultAsDTO]` для схем результату (`#[RPC\Response]` застарів), `RpcResponseContextBuilder` / `RpcErrorNormalizer` переїхали в `Ufo\RpcObject\Transformer` |
| [2.6](2.6/README-UA.md) | `#[RPC\IgnoreApi]`, типи з PHPDoc і конструктора та денормалізація `array<string, Dto>`, callback URL перевіряється через `OPTIONS`/`POST` і кешується |
| [2.7](2.7/README-UA.md) | `DTOTransformer` (DTO ↔ масив) з трейтами `IArrayConvertible` / `IArrayConstructible`, callback URL мусить мати TLD, потрібен `ext-intl` |
| [2.8](2.8/README-UA.md) | `#[RPC\Lock]`, спецпараметри одним об'єктом `$rpc` з `rayId`, валідація DTO через `#[RPC\Assertions]`, вкладені DTO через `#[RPC\DTO]`, readonly-конструктори, Symfony 7.2 |
| [2.9](2.9/README-UA.md) | `renameKeys` у `#[RPC\DTO]`, DTO-атрибути на параметрах конструктора, `fromArray(..., $renameKey): static` |

## Оновлення з 1.x

1. **Лише атрибути.** Анотації серіалізатора більше не читаються, `doctrine/annotations` прибрано. Використовуйте атрибути PHP.
2. **`RPC\Info`** втратив `getAlias()` / `getConcat()`, натомість публічні readonly-властивості. `RPC\Info`, `RPC\Assertions` і `RPC\Cache` стали `final readonly`; `RPC\Assertions` вимагає `$assertions`.
3. **Залежності:** `symfony/validator ^7.0` з 2.0, PHP 8.3 з 2.1, `ext-intl` з 2.7.3, Symfony 7.2 з 2.8.6.

## Зміни, що ламають сумісність, усередині 2.x

Кілька мінорних версій цієї лінійки змінили публічні контракти. Перевірте їх під час переходу:
- **2.1:** тип повернення `RpcResponse::getResult()` змінено з `mixed` на union-тип. У 2.1.1–2.3.x правило протоколу callback приймало лише `http`; `https`-callback відхилявся до 2.4.0.
- **2.2:** `RPC\Assertions::toArray()` повертає `{constructor, payload}` замість плоского списку.
- **2.4:** константи `RPC\Response` типізовані (`const string`).
- **2.5:** `RpcResponseContextBuilder` і `RpcErrorNormalizer` переїхали в `Ufo\RpcObject\Transformer`; `#[RPC\Response]` застарів на користь `#[RPC\ResultAsDTO]`.
- **2.8:** спеціальні параметри передаються одним об'єктом `"$rpc": {"timeout": …}` замість ключів `"$rpc.timeout"`. `SpecialRpcParams::PREFIX`, `SpecialRpcParams::DEFAULT_TIMEOUT` і `SpecialRpcParams::fromArray()` прибрано, натомість `SpecialRpcParamsEnum`. Див. [2.8](2.8/README-UA.md).

Оновлення до 3.0: див. [3.x](../3.x/README-UA.md).
