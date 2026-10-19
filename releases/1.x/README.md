[Українська](README-UA.md)

# ufo-tech/rpc-objects 1.x

Major 1 is the first public object model of the UFO-Tech JSON-RPC stack: request and response objects, batch requests with `@FROM` dependencies, `$rpc` special parameters and RPC errors built from exceptions. It was the dependency of json-rpc-bundle 4.x–5.x (`rpc-objects 1.*`). Over the 1.x line the package added:
- a response-format attribute (`RpcInfo`, later `RPC\Response`)
- PHP attributes in the `Ufo\RpcObject\RPC\` namespace (`RPC\Info`, `RPC\Response`, `RPC\Assertions`)
- date normalization in the serializer

## Requirements

| | 1.4.1 | 1.5 |
|---|---|---|
| PHP | `>=8.1` | `>=8.2` |
| Symfony (`serializer`, `validator`, `http-client`, `property-access`) | `6.*` | `6.*` |
| Other | `doctrine/annotations`, `ufo-tech/rpc-exceptions *` | `doctrine/annotations`, `ufo-tech/rpc-exceptions *` |

## Versions

| Version | Highlights |
|---|---|
| [1.4](1.4/README.md) | JSON-RPC 2.0 request/response model, batch with `@FROM:<id>(<field>)`, `$rpc.callback` / `$rpc.timeout`, `throwError()`, `RpcInfo` response format |
| [1.5](1.5/README.md) | Attributes moved to `RPC\*` (`RPC\Info` alias, `RPC\Response`, `RPC\Assertions`), dates normalized |

## Upgrading from 1.4 to 1.5

1. **PHP 8.2** is required.
2. **Renamed class:** `Ufo\RpcObject\RpcInfo` → `Ufo\RpcObject\RPC\Response`. Update imports and attribute usages.
