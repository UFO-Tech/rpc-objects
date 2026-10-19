[English](README.md)

# 3.5 — Версії API в `#[RPC\Info]` і запитах, видалено застарілі опції

Версії: 3.5.0. Перший реліз: 2026-02-22.

## Головне

- **Версії API**: `#[RPC\Info]` приймає `version` і `removedMethods`; `RpcRequest` несе `apiVersion` (за замовчуванням `v1`).
- **Прибирання**: видалено `#[RPC\ResultAsDTO]` і застарілі аргументи `RPC\DTO` / `RPC\Param`.
- Це модельна частина json-rpc-bundle 10.0 (`/rpc/v2`, `removedMethods`).

## Що нового

### `#[RPC\Info(version:, removedMethods:)]`

```php
use Ufo\RpcObject\RPC;

#[RPC\Info('user', version: 2, removedMethods: ['legacyLogin'])]
final class UserProcedureV2 extends UserProcedure {}
```

- `Info::$version` — рядок: `'v' . $version` (`'v2'`). За замовчуванням `Info::DEFAULT_VERSION` = `'v1'`.
- `Info::$removedMethods` містить повні імена методів: кожен запис обрізається і отримує префікс `alias . concat` (`'user.legacyLogin'`), дублікати відкидаються.
- `alias` тепер обов'язковий (`string`, було `?string = null`).

### `apiVersion` у запитах

`RpcRequest` має новий аргумент конструктора й публічну readonly-властивість `apiVersion` (`#[Ignore]`, не серіалізується). `RpcRequest::fromJson()` і `fromArray()` приймають її другим аргументом:

```php
$request = RpcRequest::fromJson($body, apiVersion: 'v2');
$request->apiVersion; // 'v2'
```

### Нові помічники

- `RPC\DTO::getRealFormat(string $paramName)` і `RPC\DTO::isCollection()` (перенесено з видаленого `ResultAsDTO`).
- `RPC\Param::bitFromType(string $type): int` відображає `string` / `int` / `float` на `Param::STRING` / `INT` / `FLOAT`, решту — на `Param::NULL`.
- `RPC\Param::getData(string $contextKey): mixed` читає значення з контексту параметра.

## Зміни та виправлення

- Задокументовано конструктор `RPC\Assertions` (`@param Constraint[] $assertions`).
- `AttributeTransformer` більше не містить `ResultAsDTO` у списку атрибутів.

## Примітки щодо оновлення

Цей мінорний реліз містить несумісні зміни:

1. **`#[RPC\ResultAsDTO]` видалено.** Використовуйте тип повернення або PHPDoc (`@return UserDto[]`), або `#[RPC\DTO]` / `#[RPC\DTOCollection]` на методі.
2. **`RPC\DTO`:** аргументи `renameKeys` і `transformerFQCN` видалено — передавайте `context: [DTO::C_RENAME_KEYS => […], DTO::C_TRANSFORMER => …]`. Конструктор тепер `(string $dtoFQCN, bool $collection = false, array $context = [])`.
3. **`RPC\Param`:** аргументи й публічні властивості `default` і `convertorFQCN` видалено — передавайте `context: [Param::C_DEFAULT => …, Param::C_CONVERTOR => …]` і читайте їх через `getData()`.
4. **`RPC\Info`:** `alias` обов'язковий; `#[RPC\Info]` без аргументів більше не працює.
5. Оновлюйте json-rpc-bundle разом із цим пакетом (нотатки бандла 10.0 вимагають узгоджених версій `rpc-objects` і `dto-transformer`).
