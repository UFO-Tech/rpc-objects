[English](README.md)

# 3.3 — Нормалізатори enum і DTO, колекції в `Param`, `ResultAsDTO` застарів

Версії: 3.3.0–3.3.3. Перший реліз: 2025-09-30.

## Головне

- **Enum у серіалізаторі**: новий `EnumNormalizer` перетворює enum на значення (або ім'я) і назад.
- **`DtoObjectNormalizer`** обгортає `ObjectNormalizer` Symfony: об'єкти з `IArrayConvertible` нормалізуються через `toArray()`, денормалізація йде через `DTOTransformer`.
- `#[RPC\Param]` отримав аргумент `collection`; `#[RPC\ResultAsDTO]` застарів — замість нього тип повернення / PHPDoc.

## Що нового

### `EnumNormalizer`

`Ufo\RpcObject\Transformer\EnumNormalizer` зареєстровано першим у `Transformer::getDefault()`. `BackedEnum` нормалізується в `value`, чистий `UnitEnum` — у `name`. Денормалізація приймає рядок, int або екземпляр enum; некоректне значення перетворюється на `UnexpectedValueException` Symfony.

```php
use Ufo\RpcObject\Transformer\Transformer;

enum Status: string { case Active = 'active'; }

Transformer::getDefault()->normalize(Status::Active);                 // 'active'
Transformer::getDefault()->denormalize('active', Status::class);     // Status::Active
```

### `DtoObjectNormalizer`

`Ufo\RpcObject\Transformer\DtoObjectNormalizer` замінює звичайний `ObjectNormalizer` у серіалізаторі за замовчуванням (і всередині `ConstraintObjectNormalizer` / `AssociativeArrayDenormalizer`):

- об'єкти `IArrayConvertible` → `$object->toArray()`;
- інші об'єкти → `ObjectNormalizer` плюс ключ `$className` (коротке ім'я класу) **лише** коли в контексті є `DtoObjectNormalizer::AS_SMART => true` (з 3.3.3; див. нижче);
- денормалізація → `DTOTransformer::fromArray($type, $data, namespaces: $context['namespaces'] ?? [])`.

```php
use Ufo\RpcObject\Transformer\DtoObjectNormalizer;

$data = Transformer::getDefault()->normalize($user, context: [DtoObjectNormalizer::AS_SMART => true]);
// ['name' => 'Ada', '$className' => 'UserDto']
```

### `#[RPC\Param(collection: …)]`

`RPC\Param` приймає `bool $collection = false` **другим** аргументом конструктора (перед застарілими `default` / `convertorFQCN`). `#[RPC\DTOCollection]` тепер викликає батьківський конструктор одразу з `collection: true`, а аргумент `collection` у `RPC\DTO` більше не позначений застарілим.

### `#[RPC\ResultAsDTO]` застарів

Позначено `#[Deprecated('Use docblock or return typehint')]`. Видалено в [3.5](../3.5/README-UA.md).

## Зміни та виправлення

- 3.3.0: `RpcResponse::getResult()` може повернути рядок або int для об'єктного результату (`normalizeResult(): array|string|int`), наприклад для enum.
- 3.3.0: парсер джерела `#[RPC\Assertions]` (`AttributeHelper`) більше не плутає параметр, чиє ім'я є префіксом іншого (`$id` і `$idList`).
- 3.3.1: виправлено порядок нормалізаторів — `DateTimeNormalizer` і `UidNormalizer` знову працюють перед `DtoObjectNormalizer`. У 3.3.0 дати й UUID нормалізувалися як звичайні об'єкти.
- 3.3.2: ключ `$className` більше не додається до кожного нормалізованого об'єкта; він вмикається через контекст (`AS_SIMPLE_ARRAY`).
- 3.3.3: ключ контексту перейменовано на `DtoObjectNormalizer::AS_SMART` (`'asSmart'`).
- 3.4.0 вийшла з окремої гілки `rc-3.4` між 3.3.1 і 3.3.2 (див. [3.4](../3.4/README-UA.md)); 3.3.2 і 3.3.3 продовжилися в `main`.

## Примітки щодо оновлення

1. **Уникайте 3.3.0–3.3.1**, якщо серіалізуєте об'єкти: вони додають `$className` до кожного об'єкта у відповіді, а 3.3.0 ще й ламає вивід `DateTime` / UUID. Використовуйте 3.3.3 або новішу.
2. Позиційні аргументи `RPC\Param` зсунулися: `new Param(Param::STRING, 'x')` тепер передає `'x'` як `collection`. Використовуйте іменовані аргументи (`default:`, `convertorFQCN:`).
3. У `RPC\DTO` явні аргументи `collection` / `renameKeys` / `transformerFQCN` тепер записуються **після** `context`, тож ключі, передані лише через `context`, перезаписуються значеннями за замовчуванням (для `RPC\Param` так само з 3.2). До [3.5](../3.5/README-UA.md) використовуйте іменовані аргументи.
4. Якщо ви посилалися на `DtoObjectNormalizer::AS_SIMPLE_ARRAY` (3.3.2), перейдіть на `AS_SMART`.
