[English](README.md)

# 2.3 — `@FROM` з усім результатом батьківського запиту

Версії: 2.3.0, 2.3.1. Перший реліз: 2024-07-05.

## Головне

- `@FROM:<id>()` з порожніми дужками підставляє весь результат батьківського запиту, а не одне поле.
- `RpcTransport::getDomainUrl()` повертає DSN без шляху, query та fragment (2.3.1).

## Що нового

### Весь результат батьківського запиту

```json
[
  {"jsonrpc": "2.0", "id": "u", "method": "User.get", "params": {"id": 5}},
  {"jsonrpc": "2.0", "id": "r", "method": "Report.build", "params": {"user": "@FROM:u()"}}
]
```

`RpcRequestRequire::getResponseFieldName()` у цьому випадку — порожній рядок.

### `RpcTransport::getDomainUrl()`

```php
RpcTransport::fromDsn('https://api.example.com:8443/rpc?x=1')->getDomainUrl(); // "https://api.example.com:8443"
```

## Зміни та виправлення

- 2.3.0: запит, що залежить від кількох батьківських, більше не падає, коли в результаті одного з них немає поля, потрібного посиланню на інший: id відповіді перевіряється раніше за поле.
- 2.3.0: `ResponseCreator` використовує `RpcResponse::IS_ERROR` замість літерала `'error'`.

## Примітки щодо оновлення

Немає.
