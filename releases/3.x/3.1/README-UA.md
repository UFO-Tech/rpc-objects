[English](README.md)

# 3.1 — `ConstraintsImposedException` переїхав у rpc-exceptions, тип результату `mixed`

Версії: 3.1.0–3.1.3. Перший реліз: 2025-06-30.

## Головне

- `ConstraintsImposedException` тепер береться з `ufo-tech/rpc-exceptions` (`Ufo\RpcError\ConstraintsImposedException`); копію в цьому пакеті видалено.
- `RpcResponse::getResult()` повертає `mixed`.
- Короткочасний помічник для документації (`DocsHelper`) з'явився в 3.1.0 і був видалений у 3.1.2.

## Що нового

### `ConstraintsImposedException` з rpc-exceptions (3.1.1)

Клас `Ufo\RpcObject\Rules\Validator\ConstraintsImposedException` видалено. `RpcValidator` натомість кидає `Ufo\RpcError\ConstraintsImposedException` з `ufo-tech/rpc-exceptions`.

**Як використати**

```php
use Ufo\RpcError\ConstraintsImposedException;

try {
    $rpcValidator->validateMethodParams($procedure, 'create', $params);
} catch (ConstraintsImposedException $e) {
    $errors = $e->getConstraintsImposed(); // [paramName => [повідомлення]]
}
```

### `RpcResponse::getResult(): mixed` (3.1.3)

Тип повернення розширено з `string|int|float|bool|array|object|null` до `mixed`. Поведінка не змінилася.

## Зміни та виправлення

- 3.1.0: додано `Ufo\RpcObject\DocsHelper\UfoEnumsHelper` і `XUfoValuesEnum` (ключі `x-ufo`, `x-ufo-enum`, `x-ufo-enum-name`, `x-ufo-assertions`, `x-method` для документації).
- 3.1.2: `DocsHelper` видалено. Нотатки json-rpc-bundle для 8.x фіксують такий самий відкат на боці бандла (замість `XUfoValuesEnum` використовуються буквальні ключі `x-ufo…`).

## Примітки щодо оновлення

1. Замініть `use Ufo\RpcObject\Rules\Validator\ConstraintsImposedException;` на `use Ufo\RpcError\ConstraintsImposedException;`. `composer.json` досі вимагає `ufo-tech/rpc-exceptions: *`, тож переконайтеся, що в lock-файлі версія, яка містить цей клас (json-rpc-bundle поєднує цей реліз з `ufo-tech/rpc-exceptions` ^1.3).
2. Не покладайтеся на `Ufo\RpcObject\DocsHelper\*` — він існує лише в 3.1.0–3.1.1.
3. Якщо ви перевизначаєте `RpcResponse::getResult()`, змініть тип повернення на `mixed`.
