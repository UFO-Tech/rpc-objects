[English](README.md)

# 2.9 — Перейменування ключів у `#[RPC\DTO]`, DTO-атрибути на параметрах конструктора

Версії: 2.9.0. Перший реліз: 2025-05-09.

## Головне

- `#[RPC\DTO]` і `#[RPC\ResultAsDTO]` приймають `renameKeys` і ставляться на параметри, зокрема на promoted-параметри конструктора.
- `IArrayConstructible::fromArray()` приймає `$renameKey` і повертає `static`.
- Зрозуміліші помилки про відсутні ключі в `DTOTransformer::fromArray()`; властивість можна пропустити, перейменувавши її в `null`.

## Що нового

### `renameKeys` і параметри як ціль

`#[RPC\DTO(string $dtoFQCN, bool $collection = false, array $renameKeys = [])]`: вкладений DTO будується через `DTOTransformer::fromArray($dtoFQCN, $value, $renameKeys)`. `DTO` і `ResultAsDTO` тепер можна ставити на методи, властивості й параметри.

**Як використати**

```php
use Ufo\RpcObject\RPC;

final readonly class OrderDTO
{
    public function __construct(
        #[RPC\DTO(CustomerDTO::class, renameKeys: ['fullName' => 'name'])]
        public CustomerDTO $customer,
        #[RPC\DTO(ItemDTO::class, collection: true)]
        public array $items,
    ) {}
}
```

Тут `CustomerDTO::$fullName` заповнюється з вхідного ключа `name`.

### Деталі `fromArray()`

- `IArrayConstructible::fromArray(array $data, array $renameKey = []): static`, а `ArrayConstructibleTrait` передає `$renameKey` далі.
- Перейменування в `null` (`['internalId' => null]`) пропускає цю властивість або параметр конструктора.
- Відсутній ключ кидає `Missing required key for constructor param: '<key>'` або `… for property: '<key>'`. Для властивості значення за замовчуванням береться з її оголошення або з відповідного необов'язкового параметра конструктора.

## Зміни та виправлення

- Якщо клас має `readonly`-властивості, після конструктора пропускаються лише властивості, вже передані в конструктор. Раніше пропускалися всі решта, тож властивості такого класу поза конструктором ніколи не заповнювалися.

## Примітки щодо оновлення

- Класи, що реалізують `IArrayConstructible` напряму, мають оновити сигнатуру до `fromArray(array $data, array $renameKey = []): static`.
- Змінився текст виключень про відсутні ключі; оновіть тести чи фільтри логів, які на нього спираються.
