[English](README.md)

# 2.7 — `DTOTransformer`: DTO ↔ масив

Версії: 2.7.0–2.7.3. Перший реліз: 2025-01-13.

## Головне

- Новий `Ufo\RpcObject\DTO\DTOTransformer` перетворює DTO на масив і назад через рефлексію.
- Інтерфейси `IArrayConvertible` / `IArrayConstructible` і трейти `ArrayConvertibleTrait` / `ArrayConstructibleTrait` додають вашим DTO методи `toArray()` / `fromArray()`.
- Callback URL мусить мати домен верхнього рівня; тепер потрібен `ext-intl`.

## Що нового

### `DTOTransformer`

- `DTOTransformer::toArray(object $dto)` читає всі властивості; значення, що реалізує `IArrayConvertible`, перетворюється власним `toArray()`.
- `DTOTransformer::fromArray(string $classFQCN, array $data)` створює об'єкт без конструктора і заповнює властивості з `$data`. Відсутній ключ для властивості без значення за замовчуванням кидає `InvalidArgumentException("Missing required key: '<key>'")`.

**Як використати**

```php
use Ufo\RpcObject\DTO\ArrayConstructibleTrait;
use Ufo\RpcObject\DTO\ArrayConvertibleTrait;
use Ufo\RpcObject\DTO\IArrayConstructible;
use Ufo\RpcObject\DTO\IArrayConvertible;

final class CarDTO implements IArrayConvertible, IArrayConstructible
{
    use ArrayConstructibleTrait, ArrayConvertibleTrait;

    public string $brand;
    public int $year;
}

$car = CarDTO::fromArray(['brand' => 'Volvo', 'year' => 2020]);
$car->toArray(); // ['brand' => 'Volvo', 'year' => 2020]
```

## Зміни та виправлення

- 2.7.1–2.7.2: `fromArray()` більше не кидає виключення для ключа, який є, але має значення `null`. Побічний ефект остаточної перевірки: відсутній ключ теж не помічається, властивість отримує `null` (з попередженням `Undefined array key`). Перевірку обов'язкових ключів відновлено в 2.8.
- 2.7.2: `CallbackRules` додає `requireTld: true` до кожного `Assert\Url` і до `AssertRealUrl`, тож callback на кшталт `http://localhost/hook` відхиляється (внесок у PR #1).
- 2.7.3: `AssertionsCollection` пропускає параметр без `#[RPC\Assertions]`, а не падає на відсутньому атрибуті (PR #2). `ArrayConstructibleTrait::fromArray()` створює `static::class`, тож працює в підкласах.
- 2.7.3: `ext-intl` додано до `require`.

## Примітки щодо оновлення

- Встановіть `ext-intl`.
- Callback URL без TLD (`localhost`, голі імена хостів) тепер не проходять валідацію.
