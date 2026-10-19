[English](README.md)

# 3.0 — DTO-шар винесено в `ufo-tech/dto-transformer`, `#[RPC\Param]`, `#[RPC\CacheRelation]`

Версії: 3.0.0, 3.0.1. Перший реліз: 2025-05-10.

## Головне

- Інструменти DTO (`DTOTransformer`, інтерфейси й трейти для масивів, `TypeHintResolver`, базовий валідатор) переїхали в новий пакет `ufo-tech/dto-transformer ^1.0` у простір імен `Ufo\DTO\…`.
- `RPC\DTO`, `RPC\Assertions` і `Rules\Validator\Validator` тепер розширюють класи `ufo-tech/dto-transformer`; `AttributeTransformer` відновлює RPC-атрибути з масивів.
- Нові атрибути `#[RPC\Param]` (скалярні типи для параметрів, що конвертуються) і `#[RPC\CacheRelation]` (інвалідація / прогрів пов'язаного кешу).
- Видалено застарілий `#[RPC\Response]` і простір імен `Ufo\RpcObject\Events`.

## Що нового

### `ufo-tech/dto-transformer`

Пакет тепер вимагає `ufo-tech/dto-transformer ^1.0` і спирається на нього:

- `Ufo\RpcObject\RPC\DTO extends Ufo\DTO\Attributes\AttrDTO` з новим необов'язковим аргументом `?string $transformerFQCN`.
- `Ufo\RpcObject\RPC\Assertions extends Ufo\DTO\Attributes\AttrAssertions`; `constructorArgs` став аргументом конструктора (`new Assertions($assertions, $constructorArgs)`), тож атрибут більше не змінюється через рефлексію.
- `Ufo\RpcObject\Rules\Validator\Validator extends Ufo\DTO\Helpers\Validator` і містить лише `throw()`.

**Як використати**

```php
use Ufo\DTO\ArrayConstructibleTrait;
use Ufo\DTO\ArrayConvertibleTrait;
use Ufo\DTO\Interfaces\IArrayConstructible;
use Ufo\DTO\Interfaces\IArrayConvertible;

final class CarDTO implements IArrayConvertible, IArrayConstructible
{
    use ArrayConstructibleTrait, ArrayConvertibleTrait;
    // ...
}
```

### `#[RPC\Param]`

`Ufo\RpcObject\RPC\Param(int $type, null|string|int $default = null, ?string $convertorFQCN = null)` на параметрі чи властивості оголошує, у яких скалярних типах може прийти значення. `$type` — бітова маска з `Param::STRING`, `FLOAT`, `INT`, `NULL` (скорочення `NUMBER`, `MIXED`/`ANY`, `NULLABLE_STRING`, `NULLABLE_NUMBER`); інші біти кидають `InvalidArgumentException`. `getType()` повертає тип JSON Schema або список типів. Сервер використовує атрибут для конвертерів параметрів (JsonRpcBundle 8.0).

```php
use Ufo\RpcObject\RPC;

public function find(
    #[RPC\Param(RPC\Param::NULLABLE_STRING)]
    ?\DateTimeImmutable $from = null,
): array { /* ... */ }
```

### `#[RPC\CacheRelation]`

Повторюваний атрибут методу `CacheRelation(?string $serviceFQCN = null, array $methods = [], bool $warmUp = false)`: перелічує методи (цього або іншого сервісу), кеш яких залежить від поточного методу. `cloneToClass()` копіює його з іншим класом сервісу. Саму інвалідацію виконує сервер (JsonRpcBundle 7.6).

```php
use Ufo\RpcObject\RPC;

#[RPC\CacheRelation(methods: ['get', 'list'], warmUp: true)]
public function update(int $id, string $name): bool { /* ... */ }
```

### `AttributeTransformer`

`Ufo\RpcObject\Transformer\AttributeTransformer` реалізує `Ufo\DTO\Interfaces\IDTOFromArrayTransformer`: `fromArray()` пробує відновити з масиву один із `Cache`, `Lock`, `IgnoreApi`, `CacheRelation`, `DTO`, `ResultAsDTO`, інакше кидає `NotSupportDTOException`. Це дає серверу змогу відновлювати атрибути з кешованої карти сервісів.

## Зміни та виправлення

- `RpcBatchRequest::addResult(array $result)` замінено на `addResponse(RpcResponse $response, array $result)`; відповідь також прив'язується до запиту, що її чекає.
- `RpcResponse::getResult(bool $asIs = false)`: `true` повертає сирий результат без перетворення.
- `RpcRequest` сортує іменовані параметри за ключем (`ksort`) у конструкторі; `getSpecialParams()` завжди містить `rayId` = id запиту.
- `#[RPC\ResultAsDTO]` ставиться лише на методи; отримав `getRealFormat(string $paramName)`.
- Атрибути серіалізатора імпортуються з `Symfony\Component\Serializer\Attribute\…` замість застарілого `Annotation\…`.
- 3.0.1: `AttributeHelper::getMethodArgumentAttributesAsString()` знаходить `#[Assertions([...])]`, коли між ним і параметром стоять інші атрибути, а також для типів з простором імен чи nullable; якщо нічого не знайдено, повертає `null`.

## Примітки щодо оновлення

1. **Додається `ufo-tech/dto-transformer ^1.0`** (підтягується автоматично); оновіть імпорти:

   | 2.9 | 3.0 |
   | --- | --- |
   | `Ufo\RpcObject\DTO\DTOTransformer` | `Ufo\DTO\DTOTransformer` |
   | `Ufo\RpcObject\DTO\IArrayConstructible` / `IArrayConvertible` | `Ufo\DTO\Interfaces\IArrayConstructible` / `IArrayConvertible` |
   | `Ufo\RpcObject\DTO\ArrayConstructibleTrait` / `ArrayConvertibleTrait` | `Ufo\DTO\ArrayConstructibleTrait` / `ArrayConvertibleTrait` |
   | `Ufo\RpcObject\Helpers\TypeHintResolver` | `Ufo\DTO\Helpers\TypeHintResolver` |

   `Ufo\RpcObject\DTO\DTOAttributesEnum` з цього пакета видалено.
2. **`#[RPC\Response]` видалено.** Використовуйте `#[RPC\ResultAsDTO]`.
3. **`Ufo\RpcObject\Events\*` видалено** (`RpcEvent`, `BaseRpcEvent`, `RpcRequestEvent`, `RpcAsyncRequestEvent`, `RpcAsyncOutputEvent`, `RpcPreExecuteEvent`, `RpcPostExecuteEvent`, `RpcResponseEvent`, `RpcErrorEvent`). Ранні коміти 3.0.0 ще містили їх (з `RpcPreResponseEvent` / `RpcPostResponseEvent` замість `RpcResponseEvent`); до 3.0.1 їх видалили. JsonRpcBundle 8.0 визначає власні події в `Ufo\JsonRpcBundle\EventDrivenModel\Events`.
4. `RpcBatchRequest::addResult()` → `addResponse(RpcResponse $response, array $result)`.
5. `#[RPC\ResultAsDTO]` на властивостях чи параметрах більше не дозволено: використовуйте там `#[RPC\DTO]`.
6. `Assertions::$constructorArgs` тепер аргумент конструктора; код, що встановлював його через рефлексію, має передавати його в конструктор.
7. `Rules\Validator\Validator` більше не визначає сам `validate()`, `getData()`, `getErrors()`, `hasErrors()`, `getCurrentError()` — вони приходять з `Ufo\DTO\Helpers\Validator`. Перевірте підкласи, які їх перевизначали.
