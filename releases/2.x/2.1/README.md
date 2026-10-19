[Українська](README-UA.md)

# 2.1 — Async request wrapper, transport DSN, constraints as data

Versions: 2.1.0–2.1.4. First released: 2024-05-16.

## Highlights

- `RpcAsyncRequest` wraps an `RpcRequest` with a token for asynchronous delivery.
- `RpcTransport` parses and rebuilds a transport DSN; `#[Secret]` masks credentials and secret query parameters in `toArray()`.
- `ConstraintObjectNormalizer` turns Symfony constraints into `{class, context}` so `RPC\Assertions::toArray()` can describe them (2.1.4).
- `RPC\Response` adds the DTO short name as `$dto` to the response format (2.1.1).
- PHP `>=8.3`.

## What's new

### `RpcAsyncRequest`

```php
use Ufo\RpcObject\RpcAsyncRequest;

$message = new RpcAsyncRequest($rpcRequest, token: $token);
$message->getRpcRequest();
$message->token;
```

### `RpcTransport` and `#[Secret]`

```php
use Ufo\RpcObject\RpcTransport;

$transport = RpcTransport::fromDsn('amqp://user:pass@rabbit:5672/%2f/rpc?access_token=abc');
(string) $transport;  // the original DSN
$transport->toArray(); // user/pass → "{user}"/"{pass}", query → "access_token={access_token}"
```

`Secret::QUERY_PATTERN` matches query keys containing `secret`, `access`, `token` or `key`; `#[Secret(pattern: '...')]` takes another pattern.

### Constraints as data

`Transformer::getDefault()` puts `ConstraintObjectNormalizer` first. Any `Symfony\Component\Validator\Constraint` is normalized to:

```php
['class' => Assert\Length::class, 'context' => [/* normalized constraint properties */]]
```

`RPC\Assertions::toArray()` returns a list of such items; `RPC\AssertionsCollection::toArray()` does it per parameter. The server uses this to describe validation rules in the API schema.

## Changes and fixes

- 2.1.0: `RpcValidator` resets violations between calls, so errors of a previous method no longer leak into the next one.
- 2.1.1: `RealUrlValidator` checks callbacks with `GET` instead of `OPTIONS`. `CallbackRules` protocol constraint changed from `['https']` to `['http']`.
- 2.1.2: `RpcResponse::$result` / `getResult()` typed as `string|int|float|bool|array|object|null` instead of `mixed`.
- 2.1.3: `ConstraintsImposedException` keys include the violation property path (`order[items]`), not just the parameter name.

## Upgrade notes

- PHP 8.3 is required (2.1.0).
- `RpcResponse` subclasses overriding `getResult(): mixed` must use the new return type (2.1.2).
