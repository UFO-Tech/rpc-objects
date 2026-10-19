[English](README.md)

# 3.7 — Readonly `RpcAsyncRequest` з `meta`, префікси транспортів

Версії: 3.7.0. Перший реліз: 2026-09-17.

## Головне

- `RpcAsyncRequest` — `readonly`-клас з публічними `rpcRequest`, `token` і новим масивом `meta`.
- Константи `RpcTransport::SYNC_PREFIX` (`'sync'`) і `RpcTransport::ASYNC_PREFIX` (`'rpc_async'`).
- `AssociativeArrayDenormalizer` приймає `value_type` як ім'я класу або об'єкт типу.

## Що нового

### `RpcAsyncRequest`

```php
use Ufo\RpcObject\RpcAsyncRequest;

$message = new RpcAsyncRequest($rpcRequest, token: $token, meta: ['tenant' => 'acme']);

$message->rpcRequest; // RpcRequest
$message->meta;       // ['tenant' => 'acme']
```

`getRpcRequest()` ще працює, але застарів і буде видалений у 4.0.

### Префікси `RpcTransport`

`RpcTransport::SYNC_PREFIX` і `RpcTransport::ASYNC_PREFIX` задають схеми DSN для синхронного й асинхронного транспорту, тож споживачам не треба жорстко прописувати ці рядки.

### `value_type` в `AssociativeArrayDenormalizer`

Запис контексту `value_type` може бути рядком з ім'ям класу або будь-яким об'єктом з `getClassName()` (`Type` з Symfony PropertyInfo). Інше значення кидає `InvalidArgumentException`. До 3.7 працював лише об'єкт `Type` з PropertyInfo; клас більше не імпортує `Symfony\Component\PropertyInfo\Type`.

## Зміни та виправлення

Окрім описаного вище — немає.

## Примітки щодо оновлення

1. Якщо ви наслідуєте `RpcAsyncRequest`, нащадок має бути `readonly`.
2. Замініть `$asyncRequest->getRpcRequest()` на `$asyncRequest->rpcRequest`.
