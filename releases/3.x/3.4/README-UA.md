[English](README.md)

# 3.4 — `$rpc.ignoreCache`, `RpcResponse::toArray()`, збереження `rayId` між викликами

Версії: 3.4.0, 3.4.1. Перший реліз: 2025-10-24.

## Головне

- **`$rpc.ignoreCache`**: новий спеціальний параметр, що просить сервер оминути кеш відповідей.
- **`RpcResponse::toArray(ContextBuilderInterface)`**: нормалізація всього конверта відповіді з потрібним контекстом серіалізації.
- **`rayId` зберігається**: `RpcRequest::getSpecialParams()` залишає вхідний `rayId`, а не замінює його id поточного запиту.

## Що нового

### `$rpc.ignoreCache` (3.4.1)

`SpecialRpcParamsEnum::IGNORE_CACHE` (`'ignoreCache'`) і публічна властивість `SpecialRpcParams::$ignoreCache` (за замовчуванням `false`). Прапорець входить у `SpecialRpcParams::toArray()`. Сам кеш реалізує сервер (json-rpc-bundle 9.1+ читає цей прапорець).

**Як використати**

```json
{"jsonrpc": "2.0", "id": 1, "method": "catalog.list", "params": {"$rpc": {"ignoreCache": true}}}
```

```php
$request->getRpcParams()?->ignoreCache; // true
```

### `RpcResponse::toArray()` (3.4.0)

```php
use Ufo\RpcObject\Transformer\RpcResponseContextBuilder;

$array = $response->toArray(
    (new RpcResponseContextBuilder())->withResponseSignature($response)
);
```

Метод нормалізує відповідь серіалізатором пакета з `$contextBuilder->toArray()` як контекстом.

### Передавання `rayId` (3.4.1)

`RpcRequest::getSpecialParams()` записує в `rayId` id запиту лише тоді, коли він ще не заданий (`??=`). `SpecialRpcParams::toArray()` тепер містить `rayId`, якщо він є, тож ланцюжок викликів зберігає кореневий `rayId`.

## Зміни та виправлення

- `SpecialRpcParams` став `readonly`-класом; `timeout` приймає `float|int`.
- `IRpcSpecialParamHandler` оголошує ще два методи: `setParam(string $name, mixed $value): static` і `resetParams(): static`.
- `RpcRequest::fromArray()` більше не падає на невизначеній `$sp`, коли `ParamsSplitter::split()` кидає виключення, а запит не має помилок валідації.
- `RealUrlValidator` (перевірка callback-URL): таймаут з'єднання 30 с (максимальна тривалість лишилась 2 с), без примусового заголовка `Content-Type` і без імпорту `Symfony\Component\HttpFoundation\Request` (цей пакет ніколи не був оголошеною залежністю).
- `SpecialParamsRules` створює constraint-и з іменованими аргументами (підготовка до Symfony 8).

### Історія релізу

3.4.0 зроблено в гілці `rc-3.4` 2025-10-24 від 3.3.1, тоді як `main` продовжився версіями 3.3.2 і 3.3.3. Тому 3.4.0 ще містить `DtoObjectNormalizer` з 3.3.1, який додає `$className` до кожного об'єкта (див. [3.3](../3.3/README-UA.md)). Гілку злито в `main` 2025-12-17 як 3.4.1; merge-коміт із тегом 3.4.1 на Packagist датований 2026-02-20.

## Примітки щодо оновлення

1. Використовуйте 3.4.1, а не 3.4.0.
2. Якщо ви реалізуєте `IRpcSpecialParamHandler`, додайте `setParam()` і `resetParams()`.
3. Якщо ви наслідуєте `SpecialRpcParams`, ваш клас теж має бути `readonly`.
4. Якщо ви покладалися на те, що `getSpecialParams()` скидає `rayId` на id поточного запиту, — тепер пріоритет має вхідний `rayId`.
