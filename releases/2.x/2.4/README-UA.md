[English](README.md)

# 2.4 — Об'єкти подій для json-rpc-bundle 7, `TypeHintResolver`

Версії: 2.4.0. Перший реліз: 2024-07-08.

## Головне

- `Ufo\RpcObject\Events\*`: класи й назви подій для подієвої обробки в json-rpc-bundle 7 (`rpc.request`, `rpc.pre_execute`, `rpc.error`, …).
- `RpcError::fromThrowable()` створює RPC-помилку з будь-якого виключення.
- `Helpers\TypeHintResolver` перетворює типи PHP на типи JSON Schema і навпаки.
- `RPC\Response` підтримує union-типи властивостей DTO і має `getDto()` / `isCollection()`.

## Що нового

### Події

`Events\RpcEvent` містить назви подій:

| Константа | Назва | Клас події |
| --- | --- | --- |
| `REQUEST` | `rpc.request` | `RpcRequestEvent($rpcRequest)` |
| `REQUEST_ASYNC` | `rpc.async_request` | `RpcAsyncRequestEvent($rpcRequest)` |
| `OUTPUT_ASYNC` | `rpc.async_output` | `RpcAsyncOutputEvent($rpcRequest, $batchRequest, $output)` |
| `PRE_EXECUTE` | `rpc.pre_execute` | `RpcPreExecuteEvent($rpcRequest, $service, $params)` |
| `POST_EXECUTE` | `rpc.post_execute` | `RpcPostExecuteEvent($result)` |
| `RESPONSE` | `rpc.response` | `RpcResponseEvent($response)` |
| `ERROR` | `rpc.error` | `RpcErrorEvent($rpcRequest, $exception)` — також заповнює `$rpcError` |

Усі наслідують `BaseRpcEvent` (`Symfony\Contracts\EventDispatcher\Event`) і повертають свою назву з `getEventName()`. `RpcEvent::create($name, $data)` створює відповідний клас, а для невідомої назви — загальний `RpcEvent`.

**Як використати**

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

Enum зі статичними хелперами: `normalize('int')` → `integer`, `phpToJsonSchema('int|string')` → `[['type' => 'integer'], ['type' => 'string']]`, `jsonSchemaToPhp('number')` → `float`, `mixedForJsonSchema()`.

## Зміни та виправлення

- `RpcBatchRequest::fromJson()` кидає `RpcJsonParseException` на некоректному JSON замість збою на `null`.
- Невдалий `RpcRequest::fromArray()` зберігає вхідний `method`.
- `CallbackRules` приймає callback і з `http`, і з `https` (з 2.1.1 проходив лише `http`).
- `Transformer::getDefault()` реєструє `ArrayDenormalizer`, тож масиви об'єктів можна денормалізувати.
- `AttributeHelper` знаходить також `#[Assertions([...])]` без префікса `RPC\` і більше не захоплює атрибут наступного параметра.

## Примітки щодо оновлення

- `RpcPreExecuteEvent` посилається на `Ufo\JsonRpcBundle\Server\ServiceMap\Service`, а події потребують `symfony/event-dispatcher-contracts`; жодне з цього пакет не вимагає — вони приходять разом із json-rpc-bundle 7.
- Константи `RPC\Response` типізовані (`const string`), тож нащадки, що їх перевизначають, мають зберегти тип.
