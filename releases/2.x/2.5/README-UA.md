[English](README.md)

# 2.5 — `#[RPC\ResultAsDTO]`, хелпери серіалізації відповіді переїхали сюди

Версії: 2.5.0. Перший реліз: 2024-07-14.

## Головне

- Новий атрибут `#[RPC\ResultAsDTO]` описує результат методу класом DTO (за потреби — колекцією). Він замінює `#[RPC\Response]`, який тепер позначено застарілим.
- `RpcResponseContextBuilder` і `RpcErrorNormalizer` тепер живуть у `Ufo\RpcObject\Transformer` (раніше — `Ufo\JsonRpcBundle\Serializer` у бандлі).
- `TypeHintResolver::isRealClass()` відрізняє назву класу від скалярного типу.

## Що нового

### `#[RPC\ResultAsDTO]`

`Ufo\RpcObject\RPC\ResultAsDTO(string $dtoFQCN, bool $collection = false)` ставиться на метод або властивість. Сервер читає атрибут, розбирає DTO і зберігає формат, який повертає `getResponseFormat()` (поки DTO не розібрано, метод кидає `RpcInternalException`).

**Як використати**

```php
use Ufo\RpcObject\RPC;

#[RPC\ResultAsDTO(UserDTO::class)]
public function get(int $id): UserDTO { /* ... */ }

#[RPC\ResultAsDTO(UserDTO::class, collection: true)]
public function list(): array { /* ... */ }
```

### Контекст серіалізації та нормалізатор помилок

`Ufo\RpcObject\Transformer\RpcResponseContextBuilder` збирає контекст Symfony Serializer для `RpcResponse`: `withGroup()`, `withResponseSignature()` (додає групу `result` або `error`) і `removeParent()`. `Ufo\RpcObject\Transformer\RpcErrorNormalizer` нормалізує `AbstractRpcErrorException` через `ExceptionToArrayTransformer`, коли в контексті є `RpcErrorNormalizer::RPC_CONTEXT` (`rpc_handle`); білдер ставить цей ключ за замовчуванням.

```php
use Ufo\RpcObject\Transformer\RpcResponseContextBuilder;

$context = (new RpcResponseContextBuilder())->withGroup(['detail', 'list']);
```

## Зміни та виправлення

- `RpcResponse` імпортує білдер з `Ufo\RpcObject\Transformer` замість `Ufo\JsonRpcBundle\Serializer`, тож пакет більше не залежить від класу бандла.
- `TypeHintResolver::phpToJsonSchema()` тепер повертає лише рядок: union-типи (`int|string` або масив типів) більше не розгортаються в список елементів `{type: …}`.

## Примітки щодо оновлення

- Замініть імпорти `Ufo\JsonRpcBundle\Serializer\RpcResponseContextBuilder` / `RpcErrorNormalizer` на `Ufo\RpcObject\Transformer\…`.
- `#[RPC\Response]` застарів і буде видалений у 3.0: переходьте на `#[RPC\ResultAsDTO]`.
- Якщо ви викликали `TypeHintResolver::phpToJsonSchema()` з union-типами, розбивайте типи самостійно.
