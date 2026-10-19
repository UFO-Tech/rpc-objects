[English](README.md)

# 2.2 — Групи серіалізації для результатів, вихідний текст обмежень, UUID

Версії: 2.2.0, 2.2.1. Перший реліз: 2024-07-02.

## Головне

- `RpcResponse` нормалізує об'єктні результати через context builder, тож поля DTO можна показувати чи приховувати групами серіалізації (`list`, `detail`, `group` … `group_5`).
- `RPC\Assertions::toArray()` повертає вихідний текст обмежень (`constructor`) поруч із нормалізованими обмеженнями (`payload`).
- Значення `Symfony\Component\Uid` (`Uuid`, `Ulid`) нормалізуються в рядки.

## Що нового

### Групи для результатів

Нові константи `RpcResponse`: `IS_LIST`, `IS_DETAIL`, `IS_GROUP`, `IS_GROUP_2` … `IS_GROUP_5`. Конструктор приймає необов'язковий `contextBuilder` (`Ufo\JsonRpcBundle\Serializer\RpcResponseContextBuilder`, його надає json-rpc-bundle). Об'єктні результати нормалізуються з контекстом білдера без групи конверта (`result` / `error`), тож DTO серіалізується лише із запитаними групами.

**Як використати**

```php
use Symfony\Component\Serializer\Annotation\Groups;
use Ufo\RpcObject\RpcResponse;

final class UserDTO
{
    #[Groups([RpcResponse::IS_LIST, RpcResponse::IS_DETAIL])]
    public int $id;

    #[Groups([RpcResponse::IS_DETAIL])]
    public string $email;
}
```

### Вихідний текст обмежень

`RPC\AssertionsCollection::fillAssertion($className, $method, $paramRef)` читає `#[RPC\Assertions([...])]` параметра з PHP-коду через `Transformer\AttributeHelper::getMethodArgumentAttributesAsString()` і зберігає його в `Assertions::$constructorArgs`. `Assertions::toArray()` тепер повертає:

```php
['constructor' => 'new Assert\NotBlank()', 'payload' => [['class' => ..., 'context' => [...]]]]
```

`ConstraintObjectNormalizer` повертає як `context` усі нормалізовані властивості обмеження (раніше — лише аргументи конструктора).

## Зміни та виправлення

- 2.2.1: група конверта прибирається перед нормалізацією об'єкта результату.

## Примітки щодо оновлення

- `RPC\Assertions::toArray()` змінив форму: плоский список став `['constructor' => ..., 'payload' => [...]]`.
- `RpcResponse` посилається на `Ufo\JsonRpcBundle\Serializer\RpcResponseContextBuilder`, якого немає в цьому пакеті; використовуйте разом із json-rpc-bundle.
