[Українська](README-UA.md)

# 2.2 — Serialization groups for results, assertion source text, UUIDs

Versions: 2.2.0, 2.2.1. First released: 2024-07-02.

## Highlights

- `RpcResponse` normalizes object results with a context builder, so DTO fields can be shown or hidden by serializer groups (`list`, `detail`, `group` … `group_5`).
- `RPC\Assertions::toArray()` returns the constraint source text (`constructor`) next to the normalized constraints (`payload`).
- `Symfony\Component\Uid` values (`Uuid`, `Ulid`) are normalized to strings.

## What's new

### Groups for results

New constants on `RpcResponse`: `IS_LIST`, `IS_DETAIL`, `IS_GROUP`, `IS_GROUP_2` … `IS_GROUP_5`. The constructor takes an optional `contextBuilder` (`Ufo\JsonRpcBundle\Serializer\RpcResponseContextBuilder`, provided by json-rpc-bundle). Object results are normalized with the builder's context minus the envelope group (`result` / `error`), so a DTO is serialized only with the groups you asked for.

**How to use**

```php
use Symfony\Component\Serializer\Annotation\Groups;
use Ufo\RpcObject\RpcResponse;

final class UserDTO
{
    #[Groups([RpcResponse::IS_LIST, RpcResponse::IS_DETAIL])]
    public int $id;

    #[Groups([RpcResponse::IS_DETAIL])]
    public string $email;
}
```

### Assertion source text

`RPC\AssertionsCollection::fillAssertion($className, $method, $paramRef)` reads the parameter's `#[RPC\Assertions([...])]` from the PHP source with `Transformer\AttributeHelper::getMethodArgumentAttributesAsString()` and stores it in `Assertions::$constructorArgs`. `Assertions::toArray()` now returns:

```php
['constructor' => 'new Assert\NotBlank()', 'payload' => [['class' => ..., 'context' => [...]]]]
```

`ConstraintObjectNormalizer` returns all normalized properties of a constraint as `context` (previously only constructor arguments).

## Changes and fixes

- 2.2.1: the envelope group is removed before normalizing the result object.

## Upgrade notes

- `RPC\Assertions::toArray()` changed shape: a flat list became `['constructor' => ..., 'payload' => [...]]`.
- `RpcResponse` references `Ufo\JsonRpcBundle\Serializer\RpcResponseContextBuilder`, which is not shipped by this package; use it together with json-rpc-bundle.
