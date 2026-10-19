[Українська](README-UA.md)

# 2.3 — `@FROM` with the whole parent result

Versions: 2.3.0, 2.3.1. First released: 2024-07-05.

## Highlights

- `@FROM:<id>()` with empty brackets substitutes the whole result of the parent request, not one field.
- `RpcTransport::getDomainUrl()` returns the DSN without path, query and fragment (2.3.1).

## What's new

### Whole parent result

```json
[
  {"jsonrpc": "2.0", "id": "u", "method": "User.get", "params": {"id": 5}},
  {"jsonrpc": "2.0", "id": "r", "method": "Report.build", "params": {"user": "@FROM:u()"}}
]
```

`RpcRequestRequire::getResponseFieldName()` is an empty string in this case.

### `RpcTransport::getDomainUrl()`

```php
RpcTransport::fromDsn('https://api.example.com:8443/rpc?x=1')->getDomainUrl(); // "https://api.example.com:8443"
```

## Changes and fixes

- 2.3.0: a request that depends on several parents no longer fails when one parent's result lacks a field required by another parent's reference: the response id is checked before the field.
- 2.3.0: `ResponseCreator` uses `RpcResponse::IS_ERROR` instead of the literal `'error'`.

## Upgrade notes

None.
