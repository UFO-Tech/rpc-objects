[English](README.md)

# 3.10 — dto-transformer 4, коди помилок валідації запиту

Версія: 3.10.0. Реліз: 2026-10-30.

## Головне

- Потрібен `ufo-tech/dto-transformer` ^4.0 (було ^3.1); пакет використовує його імена 4.0.
- `RpcRequestFactory` повертає помилки валідації запиту з кодом JSON-RPC 2.0, що відповідає полю: `-32600` (Invalid Request) для `id`, `method`, `jsonrpc`, `-32602` (Invalid params) для `params`. Раніше будь-яка помилка валідації мала `-32602`.
- `Rules\Validator\Validator::throw()` тепер кидає той клас винятку, який йому передали.
- `RpcResponse::getResult()` нормалізує об'єкти на будь-якій глибині масиву-результату, а не лише на першому рівні.

## Що нового

### Імена dto-transformer 4.0

Пакет звертається до dto-transformer через імена 4.0:

| Ім'я 3.x | Ім'я 4.0 |
|---|---|
| `Ufo\DTO\Interfaces\IArrayConstructible` | `Ufo\DTO\Contract\ArrayConstructibleInterface` |
| `Ufo\DTO\Interfaces\IArrayConvertible` | `Ufo\DTO\Contract\ArrayConvertibleInterface` |
| `Ufo\DTO\Interfaces\IDTOFromArrayTransformer` | `Ufo\DTO\Contract\StaticDTOFromArrayTransformerInterface` |
| `Ufo\DTO\Helpers\TypeHintResolver` | `Ufo\DTO\Schema\Type\TypeHintResolver` |

`AttributeTransformer` реалізує `StaticDTOFromArrayTransformerInterface`; `DtoObjectNormalizer` перевіряє `ArrayConvertibleInterface`. dto-transformer 4.0 лишає імена 3.x аліасами тих самих типів, тож ваші DTO на старих інтерфейсах ці перевірки проходять.

### Коди помилок у `RpcRequestFactory`

`RpcRequestFactory::fromArray()` / `fromJson()` валідують увесь запит за один прохід. Тепер клас помилки обирається за першим порушенням: порушення в `[params]` дає `RpcBadParamException` (`-32602`), у будь-якому іншому полі — `RpcBadRequestException` (`-32600`). Вибір робить новий protected `RpcRequestFactory::validationErrorClass()`.

```php
use Ufo\RpcObject\Factory\RpcRequestFactory;

RpcRequestFactory::fromArray(['id' => 1, 'params' => ['a' => 1]])->getError()->getCode();              // -32600, немає [method]
RpcRequestFactory::fromArray(['id' => 1, 'method' => 'ping', 'params' => []])->getError()->getCode(); // -32602, порожні [params]
```

Помилки в `params.$rpc` (`callback`, `timeout`, …) лишаються `-32602`.

## Зміни та виправлення

- `Rules\Validator\Validator::throw(string $class)` порівнював рядок з ім'ям класу через `instanceof`, тож перевірка завжди була хибною і кидався `RpcBadParamException`, хоч би що передали. Тепер — `is_a($class, AbstractRpcErrorException::class, true)`: нащадок `AbstractRpcErrorException` кидається як є, будь-який інший клас, як і раніше, замінюється на `RpcBadParamException`. Саме тому `RpcRequestFactory` досі ніколи не давав `-32600`.
- `AttributeTransformer::isSupportClass()` порівнює ім'я класу з `ArrayConstructibleInterface::class`.
- `RpcResponse::getResult()` нормалізував лише об'єкти, що лежать безпосередньо в масиві-результаті: DTO у списку списків чи в словнику списків поверталися об'єктами. Тепер масив обходить рекурсивно новий protected `RpcResponse::normalizeArray()`, тож `[[$dto], [$dto, $dto]]` і `['left' => [$dto], 'right' => [[$dto]]]` нормалізуються повністю; скаляри й значення, які серіалізатор не нормалізує, повертаються як є.

## Примітки до оновлення

1. **Потрібен dto-transformer 4.0.** Якщо ваш код імпортує імена 3.x, перейдіть на імена 4.0 (таблиця вище); аліаси буде видалено в dto-transformer 4.1.
2. **Змінилися коди помилок невалідних запитів.** Відсутні чи некоректні `id`, `method` або `jsonrpc` тепер дають `-32600` замість `-32602`. Клієнти й тести, що перевіряють код таких помилок, мають очікувати `-32600`. Помилки в `params` (зокрема `$rpc`) лишаються `-32602`.
3. **`Validator::throw()` враховує аргумент.** Якщо передати нащадка `AbstractRpcErrorException`, тепер кидається саме він (раніше — завжди `RpcBadParamException`).
4. **`AttributeTransformer::isSupportClass()` розпізнає лише ім'я інтерфейсу 4.0.** Тип параметра, записаний як `IArrayConstructible` (рефлексія повертає ім'я, як його написано в коді), більше не розпізнається — використовуйте `ArrayConstructibleInterface`.
