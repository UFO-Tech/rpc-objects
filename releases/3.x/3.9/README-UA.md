[English](README.md)

# 3.9 — Групи API, кеш constraint-ів валідатора, незмінний білдер контексту відповіді

Версії: 3.9.0, 3.9.1, 3.9.2 (тег 3.9.0 не зберігся). Перший реліз: 2026-10-14.

## Головне

- **`#[RPC\APIGroups]`**: позначає клас або метод як частину однієї чи кількох груп API (публічна, внутрішня, BFF…); `RpcAsyncRequest` несе `apiGroup`.
- **3.9.1 — `RpcValidator`**: constraint-и параметрів будуються один раз на метод і кешуються; помилки під час їх побудови більше не ковтаються.
- **3.9.2 — `RpcResponseContextBuilder` незмінний**: спільний білдер більше не переносить групи (наприклад, `error`) з однієї відповіді в наступну.

## Що нового

### `#[RPC\APIGroups]` (3.9.0)

`Ufo\RpcObject\RPC\APIGroups(array $groups = [], bool $withDefault = true)`, ціль: клас або метод. З `$withDefault` додається група за замовчуванням `APIGroups::DEFAULT_GROUP` (`'general'`); дублікати прибираються. Маршрутизацію за групами виконує сервер (json-rpc-bundle 10.2+).

```php
use Ufo\RpcObject\RPC;

#[RPC\Info('catalog')]
#[RPC\APIGroups(['internal', 'public'], false)]
class CatalogApi implements IRpcService
{
    #[RPC\APIGroups(['internal'], false)]
    public function updatePrice(int $productId, int $price): true { return true; }
}
```

`RpcAsyncRequest` отримав `public string $apiGroup = APIGroups::DEFAULT_GROUP`, тож асинхронне повідомлення зберігає групу, з якої прийшло.

### Кеш constraint-ів у `RpcValidator` (3.9.1)

`RpcValidator::validateMethodParams()` читає `#[RPC\Assertions]` через рефлексію один раз на `<class>::<method>` і тримає результат протягом життя валідатора (безпечно для long-running воркерів — залежить лише від коду). Параметри без assertions пропускаються.

До 3.9.1 будь-яке виключення під час читання атрибута чи валідації параметра перехоплювалося й ігнорувалося, тож некоректний constraint мовчки вимикав валідацію цього параметра. Тепер воно пробрасується і не кешується. Формат `ConstraintsImposedException` не змінився.

### Незмінний `RpcResponseContextBuilder` (3.9.2)

`withContext()`, `withGroup()`, `withResponseSignature()` і `removeParent()` повертають новий екземпляр і не змінюють оригінал — як білдери контексту Symfony. Білдер — спільний сервіс; коли він змінював сам себе, групи однієї відповіді (наприклад, `error`) лишалися в ньому, і наступний DTO-результат нормалізувався як `[]`.

```php
use Ufo\RpcObject\Transformer\RpcResponseContextBuilder;

$shared = new RpcResponseContextBuilder();
$context = $shared->withResponseSignature($response)->withGroup(['detail', 'list']);

$context->toArray()['groups']; // ['result', 'detail', 'list']
$shared->toArray();            // ['rpc_handle' => true] — без змін
```

## Зміни та виправлення

- 3.9.1: сигнатура `RpcValidator::validateParam()` змінилася на `(string $paramName, array $assertions, mixed $value)`; новий protected-метод `getMethodConstraints()`.

## Примітки щодо оновлення

1. **3.9.2: використовуйте результат методів білдера.** Код на кшталт `$builder->withGroup('detail');` з подальшим `$builder->toArray()` раніше працював, а тепер мовчки втрачає групу. Пишіть `$builder = $builder->withGroup('detail');`. Усередині пакета `RpcResponse` вже так робить.
2. 3.9.1: якщо параметр має некоректний `#[RPC\Assertions]`, виклик тепер падає з цією помилкою замість пропуску валідації — виправте constraint.
3. 3.9.1: якщо ви наслідуєте `RpcValidator` і перевизначаєте `validateParam()`, оновіть сигнатуру.
4. json-rpc-bundle 10.2 вимагає ^3.9 (для `RPC\APIGroups` і `RpcAsyncRequest::$apiGroup`), 10.3 — ^3.9.1.
