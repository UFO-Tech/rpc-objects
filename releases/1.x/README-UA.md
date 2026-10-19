[English](README.md)

# ufo-tech/rpc-objects 1.x

Мажорна версія 1 — перша публічна об'єктна модель JSON-RPC стеку UFO-Tech: об'єкти запиту й відповіді, batch-запити із залежностями `@FROM`, спеціальні параметри `$rpc` і RPC-помилки, побудовані з виключень. Від неї залежали json-rpc-bundle 4.x–5.x (`rpc-objects 1.*`). Протягом лінійки 1.x пакет отримав:
- атрибут формату відповіді (`RpcInfo`, згодом `RPC\Response`)
- атрибути PHP у просторі імен `Ufo\RpcObject\RPC\` (`RPC\Info`, `RPC\Response`, `RPC\Assertions`)
- нормалізацію дат у серіалізаторі

## Вимоги

| | 1.4.1 | 1.5 |
|---|---|---|
| PHP | `>=8.1` | `>=8.2` |
| Symfony (`serializer`, `validator`, `http-client`, `property-access`) | `6.*` | `6.*` |
| Інше | `doctrine/annotations`, `ufo-tech/rpc-exceptions *` | `doctrine/annotations`, `ufo-tech/rpc-exceptions *` |

## Версії

| Версія | Головне |
|---|---|
| [1.4](1.4/README-UA.md) | Модель запиту/відповіді JSON-RPC 2.0, batch з `@FROM:<id>(<field>)`, `$rpc.callback` / `$rpc.timeout`, `throwError()`, формат відповіді `RpcInfo` |
| [1.5](1.5/README-UA.md) | Атрибути перенесено в `RPC\*` (`RPC\Info` аліас, `RPC\Response`, `RPC\Assertions`), нормалізація дат |

## Оновлення з 1.4 до 1.5

1. Потрібен **PHP 8.2**.
2. **Перейменований клас:** `Ufo\RpcObject\RpcInfo` → `Ufo\RpcObject\RPC\Response`. Оновіть імпорти й використання атрибута.
