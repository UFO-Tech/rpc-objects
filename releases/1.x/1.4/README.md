[Українська](README-UA.md)

# 1.4 — JSON-RPC 2.0 object model, batch with `@FROM`, `$rpc` special params

Versions: 1.4.1. First released: 2023-02-01.

This is the baseline of the library. Versions before 1.4.1 were not recorded in the repository history, so this page describes everything up to commit `63d991b` (2023-07-26).

## Highlights

- `RpcRequest`, `RpcResponse`, `RpcError`: the JSON-RPC 2.0 request/response model with validation on construction.
- `RpcBatchRequest`: a batch queue where one request can take a value from another request's result with `@FROM:<id>(<field>)`.
- `$rpc.` special params (`$rpc.callback`, `$rpc.timeout`) split from regular params into `SpecialRpcParams`.
- `RpcResponse::throwError()` and `ResponseCreator::fromJson($json, catchError: false)` turn an RPC error back into an exception.
- `RpcInfo` attribute describes the response format of a method, optionally from a DTO class.

## What's new

### Requests

`RpcRequest` takes `id`, `method`, `params`, `version` (serialized as `jsonrpc`) and the raw JSON. The constructor validates the fields with `Rules\RequestRules`; an empty `id` is replaced with `uniqid()`. `RpcRequest::fromArray()` / `fromJson()` do not throw on invalid input: they build the object without the constructor and store the exception, available through `hasError()` / `getError()` and `isUserError()` / `isProcedureError()` / `isServerError()`.

**How to use**

```php
use Ufo\RpcObject\RpcRequest;

$request = RpcRequest::fromJson('{"jsonrpc":"2.0","id":1,"method":"User.get","params":{"id":5}}');

if ($request->hasError()) {
    // $request->getError() — the validation exception
}
$request->getMethod(); // "User.get"
$request->getParams(); // ["id" => 5]
```

### Batch with dependencies

`RpcBatchRequest::fromJson()` builds a queue. A scalar parameter `@FROM:<requestId>(<field>)` creates an `RpcRequestRequire`; the request waits until the parent result arrives through `addResult()` and the value of `<field>` is substituted. If the parent returned an error or has no such field, the dependent request gets `RpcBadRequestException`.

**How to use**

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
    // execute, then:
    $batch->addResult($responseArray);
}
$batch->getResults();
```

### Special params

Keys with the `$rpc.` prefix are moved out of `params` by `Rules\ParamsSplitter` into `SpecialRpcParams` (`$rpc.callback` → `CallbackObject`, `$rpc.timeout`, default 10 seconds). A callback makes the request asynchronous (`RpcRequest::isAsync()`); its URL is checked by `Rules\CallbackRules`, including `AssertRealUrl`, which sends a real HTTP request with a 1-second timeout.

### Responses and errors

`RpcResponse` serializes `id`, `jsonrpc` and either `result` or `error` (serializer groups `result` / `error`). Since 1.4.1, an object in `result` is normalized through `Transformer::getDefault()`; since commit `63d991b`, objects inside a list too. `throwError()` rethrows the error as `AbstractRpcErrorException` (from `ufo-tech/rpc-exceptions`). `RpcError::$data` accepts a `Throwable` or an array.

```php
use Ufo\RpcObject\Transformer\ResponseCreator;

$response = ResponseCreator::fromJson($json);         // error is kept in $response->getError()
ResponseCreator::fromJson($json, catchError: false);  // error is thrown
$response->throwError();
```

### `RpcInfo`

`#[RpcInfo(responseFormat: [...])]` or `#[RpcInfo(dto: UserDTO::class, collection: true)]` describes a method's response; with `dto`, the format is built from the public typed properties of the class.

## Changes and fixes

- 2023-07: `@FROM` is matched only in scalar params; a failed `fromArray()` keeps `id` and `jsonrpc` of the input.
- 1.4.1: `RpcInfo` got `collection`; `RpcResponse::$result` accepts any type.

## Requirements

PHP `>=8.1`, `symfony/serializer`, `symfony/validator`, `symfony/http-client`, `symfony/property-access` `6.*`, `doctrine/annotations`, `ufo-tech/rpc-exceptions`.
