[English](README.md)

# 3.2 — dto-transformer 2, `#[RPC\DTOCollection]`, опції атрибутів через context

Версії: 3.2.0. Перший реліз: 2025-09-29.

## Головне

- Потрібен `ufo-tech/dto-transformer` ^2.0 (було ^1.0).
- Новий `#[RPC\DTOCollection(X::class)]` — скорочення для колекції DTO.
- `#[RPC\DTO]` і `#[RPC\Param]` приймають масив `context`; старі позиційні опції позначено застарілими.

## Що нового

### `#[RPC\DTOCollection]`

`Ufo\RpcObject\RPC\DTOCollection` наслідує `RPC\DTO` і завжди вмикає ознаку колекції. Цілі: метод, властивість, параметр.

**Як використати**

```php
use Ufo\RpcObject\RPC;

final class OrderProcedure implements IRpcService
{
    public function import(
        #[RPC\DTOCollection(OrderLineDto::class)]
        array $lines,
    ): int { /* ... */ }
}
```

### `context` для `#[RPC\DTO]` і `#[RPC\Param]`

Опції `RPC\DTO` зберігаються в масиві context з ключами з `Ufo\DTO\Attributes\AttrDTO`: `C_COLLECTION`, `C_RENAME_KEYS`, `C_TRANSFORMER`. Аргументи конструктора `collection`, `renameKeys` і `transformerFQCN` ще працюють, але позначені `@deprecated`.

`RPC\Param` змінено так само: нові константи `C_DEFAULT`, `C_CONVERTOR`, `C_COLLECTION`, публічна властивість `$context` і `isCollection()`. Аргументи `default` і `convertorFQCN` — `@deprecated`.

```php
#[RPC\DTO(UserDto::class, context: [
    RPC\DTO::C_COLLECTION => true,
    RPC\DTO::C_RENAME_KEYS => ['user_name' => 'name'],
])]
```

## Зміни та виправлення

- `AttributeTransformer::fromArray()` приймає 4-й аргумент `array $namespaces = []` і передає його в `DTOTransformer::fromArray()` (API dto-transformer 2).
- У `RPC\Param` явні аргументи `default` / `convertorFQCN` записуються після `context`, тож ці ключі, передані лише через `context`, не діють — їх перезаписує `null`. До 3.5, де аргументи видалено, використовуйте іменовані аргументи.

## Примітки щодо оновлення

1. Оновіть `ufo-tech/dto-transformer` до ^2.0 разом із цим пакетом.
2. Якщо ви наслідуєте `AttributeTransformer`, додайте аргумент `$namespaces` у `fromArray()`.
3. Почніть переносити `renameKeys:` / `transformerFQCN:` / `collection: true` у `context:` або `#[RPC\DTOCollection]`; аргументи видалено в [3.5](../3.5/README-UA.md).
