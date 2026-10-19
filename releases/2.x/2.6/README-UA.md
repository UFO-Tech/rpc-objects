[English](README.md)

# 2.6 — `#[RPC\IgnoreApi]`, денормалізатор асоціативних масивів, суворіша перевірка callback URL

Версії: 2.6.0–2.6.4. Перший реліз: 2024-07-24.

## Головне

- Новий атрибут `#[RPC\IgnoreApi]`: сервер може пропустити публічний метод, коли будує API.
- Стандартний серіалізатор читає типи з PHPDoc і конструктора та денормалізує асоціативні масиви DTO (`array<string, Dto>`).
- `AssertRealUrl` перевіряє callback URL через `OPTIONS`/`POST` і кешує результат для кожного URL.

## Що нового

### `#[RPC\IgnoreApi]`

`Ufo\RpcObject\RPC\IgnoreApi` — порожній атрибут-маркер для методів. Пакет лише визначає його, а що з ним робити, вирішує RPC-сервер (JsonRpcBundle 7.2 прибирає такі методи з API та документації).

**Як використати**

```php
use Ufo\RpcObject\RPC;

#[RPC\IgnoreApi]
public function setFormatter(Formatter $f): void { /* не RPC-метод */ }
```

### Серіалізатор: екстрактори типів і асоціативні масиви

`Transformer::getDefault()` створює `ObjectNormalizer` з `PropertyInfoExtractor` (`ConstructorExtractor` + `PhpDocExtractor`, `ReflectionExtractor`, `SerializerExtractor`) замість голого `ReflectionExtractor`, тож `@var Dto[]` і PHPDoc конструктора враховуються. Новий `Ufo\RpcObject\Transformer\AssociativeArrayDenormalizer` обробляє масиви з непослідовними ключами: якщо в контексті є `value_type`, кожен елемент-масив денормалізується в цей клас, інші значення лишаються як є.

Остаточний порядок нормалізаторів (2.6.3): `DateTimeNormalizer`, `UidNormalizer`, `ConstraintObjectNormalizer`, `ObjectNormalizer`, `AssociativeArrayDenormalizer`, `ArrayDenormalizer`. JSON-енкодер зареєстровано під ключем `json`.

## Зміни та виправлення

- 2.6.1: до стандартного серіалізатора додано екстрактори типів, змінено порядок нормалізаторів.
- 2.6.2: додано `AssociativeArrayDenormalizer` (значення копіюються як є).
- 2.6.3: `AssociativeArrayDenormalizer` отримує object normalizer і денормалізує вкладені DTO за `value_type`; `DateTimeNormalizer` і `UidNormalizer` перенесено на початок ланцюжка.
- 2.6.4: `RealUrlValidator` надсилає `OPTIONS` (таймаут 2 с) і приймає URL, якщо `Allow` містить `POST`; інакше надсилає `POST` з `{"rpc.callback": "check real webhook url"}` і приймає будь-який статус нижче 400. Результати кешуються в статичному масиві на час життя процесу. Порожні значення пропускаються ще до перевірки формату URL.
- 2.6.4: якщо конструктор падає, `RpcRequest::fromArray()` створює запасний об'єкт без нього; тепер цей об'єкт теж отримує `params` (раніше властивість лишалася неініціалізованою).

## Примітки щодо оновлення

- `RealUrlValidator` використовує константи `Symfony\Component\HttpFoundation\Request`, але `symfony/http-foundation` немає в `require` цього пакета. Встановіть його, якщо у вашому застосунку його ще немає.
- Callback-ендпоінти тепер перевіряються запитом `OPTIONS` і, можливо, тестовим `POST`; переконайтеся, що ендпоінт це витримує.
