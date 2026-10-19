[English](README.md)

# 1.4 — Об'єктна модель JSON-RPC 2.0, batch з `@FROM`, спеціальні параметри `$rpc`

Версії: 1.4.1. Перший реліз: 2023-02-01.

Це базова версія бібліотеки. Версії до 1.4.1 в історії репозиторію не зафіксовані, тому сторінка описує все до коміту `63d991b` (2023-07-26).

## Головне

- `RpcRequest`, `RpcResponse`, `RpcError`: модель запиту/відповіді JSON-RPC 2.0 з валідацією під час створення.
- `RpcBatchRequest`: черга batch, де запит може взяти значення з результату іншого запиту через `@FROM:<id>(<field>)`.
- Спеціальні параметри `$rpc.` (`$rpc.callback`, `$rpc.timeout`) відокремлюються від звичайних у `SpecialRpcParams`.
- `RpcResponse::throwError()` і `ResponseCreator::fromJson($json, catchError: false)` перетворюють RPC-помилку назад на виключення.
- Атрибут `RpcInfo` описує формат відповіді методу, зокрема з класу DTO.

## Що нового

### Запити

`RpcRequest` приймає `id`, `method`, `params`, `version` (серіалізується як `jsonrpc`) і сирий JSON. Конструктор валідує поля через `Rules\RequestRules`; порожній `id` замінюється на `uniqid()`. `RpcRequest::fromArray()` / `fromJson()` не кидають виключення на некоректному вводі: об'єкт створюється без конструктора, а виключення зберігається й доступне через `hasError()` / `getError()` та `isUserError()` / `isProcedureError()` / `isServerError()`.

**Як використати**

```php
use Ufo\RpcObject\RpcRequest;

$request = RpcRequest::fromJson('{"jsonrpc":"2.0","id":1,"method":"User.get","params":{"id":5}}');

if ($request->hasError()) {
    // $request->getError() — виключення валідації
}
$request->getMethod(); // "User.get"
$request->getParams(); // ["id" => 5]
```

### Batch із залежностями

`RpcBatchRequest::fromJson()` будує чергу. Скалярний параметр `@FROM:<requestId>(<field>)` створює `RpcRequestRequire`; запит чекає, поки результат батьківського запиту надійде через `addResult()`, і підставляє значення `<field>`. Якщо батьківський запит повернув помилку або не має такого поля, залежний запит отримує `RpcBadRequestException`.

**Як використати**

```json
[
  {"jsonrpc": "2.0", "id": "a", "method": "User.create", "params": {"name": "Ada"}},
  {"jsonrpc": "2.0", "id": "b", "method": "Order.create", "params": {"userId": "@FROM:a(id)"}}
]
```

```php
use Ufo\RpcObject\RpcBatchRequest;

$batch = RpcBatchRequest::fromJson($json);
foreach ($batch->getReadyToHandle() as $request) {
    // виконати, потім:
    $batch->addResult($responseArray);
}
$batch->getResults();
```

### Спеціальні параметри

Ключі з префіксом `$rpc.` `Rules\ParamsSplitter` переносить з `params` у `SpecialRpcParams` (`$rpc.callback` → `CallbackObject`, `$rpc.timeout`, за замовчуванням 10 секунд). Callback робить запит асинхронним (`RpcRequest::isAsync()`); його URL перевіряє `Rules\CallbackRules`, зокрема `AssertRealUrl`, який робить справжній HTTP-запит з таймаутом 1 секунда.

### Відповіді та помилки

`RpcResponse` серіалізує `id`, `jsonrpc` і або `result`, або `error` (групи серіалізації `result` / `error`). З 1.4.1 об'єкт у `result` нормалізується через `Transformer::getDefault()`; з коміту `63d991b` — також об'єкти всередині списку. `throwError()` кидає помилку як `AbstractRpcErrorException` (з `ufo-tech/rpc-exceptions`). `RpcError::$data` приймає `Throwable` або масив.

```php
use Ufo\RpcObject\Transformer\ResponseCreator;

$response = ResponseCreator::fromJson($json);         // помилка лишається в $response->getError()
ResponseCreator::fromJson($json, catchError: false);  // помилка кидається
$response->throwError();
```

### `RpcInfo`

`#[RpcInfo(responseFormat: [...])]` або `#[RpcInfo(dto: UserDTO::class, collection: true)]` описує відповідь методу; з `dto` формат будується з публічних типізованих властивостей класу.

## Зміни та виправлення

- 2023-07: `@FROM` шукається лише в скалярних параметрах; невдалий `fromArray()` зберігає `id` і `jsonrpc` вхідних даних.
- 1.4.1: у `RpcInfo` з'явився `collection`; `RpcResponse::$result` приймає будь-який тип.

## Вимоги

PHP `>=8.1`, `symfony/serializer`, `symfony/validator`, `symfony/http-client`, `symfony/property-access` `6.*`, `doctrine/annotations`, `ufo-tech/rpc-exceptions`.
