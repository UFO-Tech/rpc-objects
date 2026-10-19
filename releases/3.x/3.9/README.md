[Українська](README-UA.md)

# 3.9 — API groups, cached validator constraints, immutable response context builder

Versions: 3.9.0, 3.9.1, 3.9.2 (the 3.9.0 tag was not preserved). First released: 2026-10-14.

## Highlights

- **`#[RPC\APIGroups]`**: mark a class or method as part of one or more API groups (public, internal, BFF…); `RpcAsyncRequest` carries `apiGroup`.
- **3.9.1 — `RpcValidator`**: parameter constraints are built once per method and cached; errors while building them are no longer swallowed.
- **3.9.2 — `RpcResponseContextBuilder` is immutable**: a shared builder no longer leaks groups (e.g. `error`) from one response into the next.

## What's new

### `#[RPC\APIGroups]` (3.9.0)

`Ufo\RpcObject\RPC\APIGroups(array $groups = [], bool $withDefault = true)`, target: class or method. With `$withDefault` the default group `APIGroups::DEFAULT_GROUP` (`'general'`) is added; duplicates are removed. Routing by group is done by the server (json-rpc-bundle 10.2+).

```php
use Ufo\RpcObject\RPC;

#[RPC\Info('catalog')]
#[RPC\APIGroups(['internal', 'public'], false)]
class CatalogApi implements IRpcService
{
    #[RPC\APIGroups(['internal'], false)]
    public function updatePrice(int $productId, int $price): true { return true; }
}
```

`RpcAsyncRequest` got `public string $apiGroup = APIGroups::DEFAULT_GROUP`, so an async message keeps the group it came from.

### Constraint cache in `RpcValidator` (3.9.1)

`RpcValidator::validateMethodParams()` reads `#[RPC\Assertions]` through reflection once per `<class>::<method>` and keeps the result for the life of the validator (safe for long-running workers — it depends only on code). Parameters without assertions are skipped.

Before 3.9.1 any exception while reading the attribute or validating a parameter was caught and ignored, so a misconfigured constraint silently disabled validation of that parameter. Now it propagates and is not cached. The `ConstraintsImposedException` format did not change.

### Immutable `RpcResponseContextBuilder` (3.9.2)

`withContext()`, `withGroup()`, `withResponseSignature()` and `removeParent()` return a new instance and leave the original unchanged, like Symfony context builders. The builder is a shared service; when it mutated itself, the groups of one response (e.g. `error`) stayed in it, and the next DTO result was normalized as `[]`.

```php
use Ufo\RpcObject\Transformer\RpcResponseContextBuilder;

$shared = new RpcResponseContextBuilder();
$context = $shared->withResponseSignature($response)->withGroup(['detail', 'list']);

$context->toArray()['groups']; // ['result', 'detail', 'list']
$shared->toArray();            // ['rpc_handle' => true] — unchanged
```

## Changes and fixes

- 3.9.1: `RpcValidator::validateParam()` signature changed to `(string $paramName, array $assertions, mixed $value)`; new protected `getMethodConstraints()`.

## Upgrade notes

1. **3.9.2: use the return value of the builder methods.** Code like `$builder->withGroup('detail');` followed by `$builder->toArray()` used to work and now silently loses the group. Write `$builder = $builder->withGroup('detail');`. Inside this package `RpcResponse` already does so.
2. 3.9.1: if a parameter has an invalid `#[RPC\Assertions]`, the call now fails with that error instead of skipping validation — fix the constraint.
3. 3.9.1: if you extend `RpcValidator` and override `validateParam()`, update the signature.
4. json-rpc-bundle 10.2 requires ^3.9 (for `RPC\APIGroups` and `RpcAsyncRequest::$apiGroup`), 10.3 requires ^3.9.1.
