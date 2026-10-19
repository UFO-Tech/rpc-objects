[English](README.md)

# 3.8 — JSON-RPC notifications, `RpcRequestFactory`, Symfony 8

Версії: 3.8.2 (попередні теги 3.8 не збереглися). Перший реліз: 2026-09-25.

## Головне

- **JSON-RPC notifications**: повідомлення без ключа `id` стає `RpcNotificationRequest`; серіалізується без `id`.
- **`RpcRequestFactory`**: єдина точка розбору запитів і вибору класу; `RpcRequest::fromArray()` / `fromJson()` і батчі делегують їй.
- **Підтримка Symfony 8**: `symfony/serializer`, `symfony/validator` і новий явний `symfony/property-info` приймають `^7.2|^8.0`.
- **Виправлено валідацію `$rpc`**: спеціальні параметри перевіряються всередині `params.$rpc`, а не як параметри верхнього рівня.

## Що нового

### Notifications

`Ufo\RpcObject\RpcNotificationRequest` наслідує `RpcRequest`. Він отримує внутрішній id з `RpcRequest::generateId()` (потрібен для черг батчу), але `toArray()` і `refreshRawJson()` не віддають `id`. Нотифікацію створює лише відсутній ключ `id`; `"id": null` або некоректний id лишаються звичайним запитом з помилкою валідації.

Пакет не надсилає відповідей. Споживач придушує відповідь через `instanceof RpcNotificationRequest`, зокрема в батчах (json-rpc-bundle 10.1 відповідає `204`).

### `RpcRequestFactory`

**Як використати**

```php
use Ufo\RpcObject\Factory\RpcRequestFactory;
use Ufo\RpcObject\RpcNotificationRequest;

$request = RpcRequestFactory::fromArray([
    'jsonrpc' => '2.0', 'id' => 0, 'method' => 'users.find', 'params' => ['name' => 'Ada'],
]); // RpcRequest, id лишається 0

$notification = RpcRequestFactory::fromJson('{"jsonrpc":"2.0","method":"users.refresh"}', apiVersion: 'v2');
$notification instanceof RpcNotificationRequest; // true
```

`fromArray(array $data, string $apiVersion = 'v1', ?string $classFQCN = null)`. Виклик через нащадка (`MyRequest::fromArray()`) зберігає цього нащадка. Некоректне повідомлення, як і раніше, повертається об'єктом з помилкою в `hasError()` / `getError()`; тепер це стосується й виключень з конструктора, а резервні поля перевіряються за типом.

### Symfony 8 (3.8.x)

Вимоги: `symfony/serializer` і `symfony/validator` `^7.2|^8.0`, новий `symfony/property-info` `^7.2|^8.0`, `ufo-tech/rpc-exceptions` `^2.0.1` (було `*`), `ufo-tech/dto-transformer` `^3.1`.

### Валідація спеціальних параметрів (3.8.2)

`RequestRules::assertParams()` перевіряє `params.$rpc` через `SpecialParamsRules::assertAllParamsCollection()`. Раніше правила `timeout` / `callback` застосовувалися до параметрів верхнього рівня з такими іменами, тож звичайний параметр `timeout` міг бути відхилений, а сам `$rpc` не перевірявся. Також:

- `params: null` відхиляється (раніше `Type('array')` пропускав `null`); позиційні параметри (`["Ada", 42]`) лишаються валідними.
- `$rpc` має бути масивом; невідомі ключі всередині дозволені.
- `timeout` має бути `int|float` у межах `10..120`; рядок `"30"` відхиляється.
- `callback: null` відхиляється (`NotNull` замість `NotBlank`).

## Зміни та виправлення

- `RpcRequest::S_GROUP` змінено з `'raw'` на ім'я класу; `toArray()` і `refreshRawJson()` використовують `static::class` як групу серіалізації.
- id `0` (або `"0"`) більше не замінюється згенерованим; замінюється лише `''`.
- `RequestRules::assertAll(?string $classFqcn = null)` прибирає правило `id` для нотифікацій.
- `RpcBatchRequest::fromJson()` розбирає кожен елемент через `RpcRequestFactory`.
- Пакет отримав набір тестів PHPUnit 11 (`composer test`, `composer test:coverage`); у `composer.json` більше немає поля `version`.

## Примітки щодо оновлення

1. Якщо ви серіалізували запити з групою `'raw'`, використовуйте `RpcRequest::S_GROUP` (тепер `RpcRequest::class`) або `static::class`.
2. Перевірте код, який вважав `id: 0` відсутнім id.
3. Запити без `id` тепер `RpcNotificationRequest`; не надсилайте на них відповідь.
4. Оновіть `ufo-tech/rpc-exceptions` до ^2.0.1 і `ufo-tech/dto-transformer` до ^3.1.
