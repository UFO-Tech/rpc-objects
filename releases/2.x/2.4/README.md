[Українська](README-UA.md)

# 2.4 — Event objects for json-rpc-bundle 7, `TypeHintResolver`

Versions: 2.4.0. First released: 2024-07-08.

## Highlights

- `Ufo\RpcObject\Events\*`: event classes and names for the event-driven pipeline of json-rpc-bundle 7 (`rpc.request`, `rpc.pre_execute`, `rpc.error`, …).
- `RpcError::fromThrowable()` builds an RPC error from any exception.
- `Helpers\TypeHintResolver` maps PHP types to JSON Schema types and back.
- `RPC\Response` handles union-typed DTO properties and exposes `getDto()` / `isCollection()`.

## What's new

### Events

`Events\RpcEvent` holds the event names:

| Constant | Name | Event class |
| --- | --- | --- |
| `REQUEST` | `rpc.request` | `RpcRequestEvent($rpcRequest)` |
| `REQUEST_ASYNC` | `rpc.async_request` | `RpcAsyncRequestEvent($rpcRequest)` |
| `OUTPUT_ASYNC` | `rpc.async_output` | `RpcAsyncOutputEvent($rpcRequest, $batchRequest, $output)` |
| `PRE_EXECUTE` | `rpc.pre_execute` | `RpcPreExecuteEvent($rpcRequest, $service, $params)` |
| `POST_EXECUTE` | `rpc.post_execute` | `RpcPostExecuteEvent($result)` |
| `RESPONSE` | `rpc.response` | `RpcResponseEvent($response)` |
| `ERROR` | `rpc.error` | `RpcErrorEvent($rpcRequest, $exception)` — also fills `$rpcError` |

All extend `BaseRpcEvent` (`Symfony\Contracts\EventDispatcher\Event`) and return their name from `getEventName()`. `RpcEvent::create($name, $data)` instantiates the matching class, or a generic `RpcEvent` for unknown names.

**How to use**

```php
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Ufo\RpcObject\Events\RpcErrorEvent;
use Ufo\RpcObject\Events\RpcEvent;

#[AsEventListener(RpcEvent::ERROR)]
final class LogRpcErrors
{
    public function __construct(private LoggerInterface $logger) {}

    public function __invoke(RpcErrorEvent $event): void
    {
        $this->logger->error($event->rpcError->getMessage(), ['method' => $event->rpcRequest->getMethod()]);
    }
}
```

### `TypeHintResolver`

An enum with static helpers: `normalize('int')` → `integer`, `phpToJsonSchema('int|string')` → `[['type' => 'integer'], ['type' => 'string']]`, `jsonSchemaToPhp('number')` → `float`, `mixedForJsonSchema()`.

## Changes and fixes

- `RpcBatchRequest::fromJson()` throws `RpcJsonParseException` for invalid JSON instead of failing on `null`.
- A failed `RpcRequest::fromArray()` keeps the incoming `method`.
- `CallbackRules` accepts both `http` and `https` callbacks (since 2.1.1 only `http` passed).
- `Transformer::getDefault()` registers `ArrayDenormalizer`, so arrays of objects can be denormalized.
- `AttributeHelper` also finds `#[Assertions([...])]` without the `RPC\` prefix and no longer captures the next parameter's attribute.

## Upgrade notes

- `RpcPreExecuteEvent` references `Ufo\JsonRpcBundle\Server\ServiceMap\Service`, and events need `symfony/event-dispatcher-contracts`; neither is required by this package — they come with json-rpc-bundle 7.
- `RPC\Response` constants are typed (`const string`), so subclasses that redeclare them must keep the type.
