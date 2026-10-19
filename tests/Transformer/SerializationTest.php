<?php

namespace Ufo\RpcObject\Tests\Transformer;

use PHPUnit\Framework\TestCase;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Validator\Constraints as Assert;
use Ufo\DTO\DTOTransformer;
use Ufo\RpcError\RpcBadRequestException;
use Ufo\RpcObject\RpcResponse;
use Ufo\RpcObject\RpcError;
use Ufo\RpcObject\Tests\Fixtures\{Person, Convertible, Status};
use Ufo\RpcObject\Transformer\{Transformer, DtoObjectNormalizer, ConstraintObjectNormalizer, RpcErrorNormalizer, RpcResponseContextBuilder, ResponseCreator, AttributeTransformer};
use Ufo\RpcObject\RPC\Cache;

class SerializationTest extends TestCase
{
    /**
     * Перевіряє повторне використання стандартного серіалізатора, нормалізацію об’єктів, enum і дат та
     * відновлення DTO з масиву.
     */
    public function testDefaultSerializerIsReusedAndHandlesObjectsEnumsAndDates(): void
    {
        $serializer = Transformer::getDefault();
        self::assertSame($serializer, Transformer::getDefault());
        self::assertSame(['name' => 'Ada', 'age' => 37], $serializer->normalize(new Person('Ada', 37)));
        self::assertSame('active', $serializer->normalize(Status::Active));
        self::assertSame('2025-01-02T03:04:05+00:00', $serializer->normalize(new \DateTimeImmutable('2025-01-02T03:04:05Z')));
        $person = $serializer->denormalize(['name' => 'Grace', 'age' => 40], Person::class);
        self::assertEquals(new Person('Grace', 40), $person);
    }

    /**
     * Перевіряє власне перетворення IArrayConvertible, додавання назви DTO у розширеному режимі та
     * визначення підтримуваних об’єктів.
     */
    public function testConvertibleAndSmartNormalization(): void
    {
        $normalizer = new DtoObjectNormalizer(new ObjectNormalizer());
        $serializer = new Serializer([$normalizer]);
        self::assertSame(['custom' => true], $serializer->normalize(new Convertible()));
        self::assertSame(['name' => 'Ada', 'age' => 0, DTOTransformer::DTO_CLASSNAME => 'Person'], $serializer->normalize(new Person('Ada'), context: [DtoObjectNormalizer::AS_SMART => true]));
        self::assertFalse($normalizer->supportsNormalization(Status::Active));
        self::assertTrue($normalizer->supportsNormalization(new Person('Ada')));
        self::assertTrue($normalizer->supportsDenormalization([], Person::class));
        self::assertNotEmpty($normalizer->getSupportedTypes(null));
    }

    /**
     * Перевіряє перетворення порожнього ArrayObject, повернутого внутрішнім нормалізатором, на звичайний
     * масив.
     */
    public function testEmptyObjectArrayObjectIsConvertedToArray(): void
    {
        $normalizer = new DtoObjectNormalizer(new ObjectNormalizer());
        self::assertSame([], $normalizer->normalize(new \stdClass(), context: [ObjectNormalizer::PRESERVE_EMPTY_OBJECTS => true]));
    }

    /**
     * Перевіряє відновлення DTO за коротким ім’ям класу через мапу просторів імен у контексті.
     */
    public function testDtoDenormalizationResolvesShortNameUsingContext(): void
    {
        $normalizer = new DtoObjectNormalizer(new ObjectNormalizer());
        self::assertEquals(new Person('Ada'), $normalizer->denormalize(['name' => 'Ada'], 'Person', context: ['namespaces' => ['Person' => Person::class]]));
    }

    /**
     * Перевіряє включення класу та контексту обмеження валідації до результату й делегування відновлення
     * об’єктів нормалізатору DTO.
     */
    public function testConstraintNormalizerWrapsClassAndDelegatesConversion(): void
    {
        $normalizer = new ConstraintObjectNormalizer(new DtoObjectNormalizer(new ObjectNormalizer()));
        $normalizer->setSerializer(new Serializer([new ObjectNormalizer()]));
        $constraint = new Assert\NotBlank();
        self::assertTrue($normalizer->supportsNormalization($constraint));
        self::assertFalse($normalizer->supportsNormalization(new Person('Ada')));
        $data = $normalizer->normalize($constraint);
        self::assertSame(Assert\NotBlank::class, $data['class']);
        self::assertSame($constraint->message, $data['context']['message']);
        self::assertTrue($normalizer->supportsDenormalization(['name' => 'Ada'], Person::class));
        self::assertEquals(new Person('Ada'), $normalizer->denormalize(['name' => 'Ada'], Person::class));
        self::assertSame(['object' => true], $normalizer->getSupportedTypes(null));
    }

    /**
     * Перевіряє підтримку RPC-помилок лише у відповідному контексті, приховування діагностики у prod і її
     * включення разом із попереднім винятком у dev.
     */
    public function testErrorNormalizerRequiresRpcContextAndHidesDebugDataInProd(): void
    {
        $error = new RpcBadRequestException('bad', -32600, new \RuntimeException('previous'));
        $normalizer = new RpcErrorNormalizer('prod');
        self::assertFalse($normalizer->supportsNormalization($error));
        self::assertTrue($normalizer->supportsNormalization($error, context: ['rpc_handle' => true]));
        self::assertFalse($normalizer->supportsNormalization(new \RuntimeException(), context: ['rpc_handle' => true]));
        self::assertSame(['exception' => RpcBadRequestException::class], $normalizer->normalize($error));
        $debug = (new RpcErrorNormalizer('dev'))->normalize($error);
        self::assertSame('bad', $debug['message']);
        self::assertSame(-32600, $debug['code']);
        self::assertSame('previous', $debug['previous']['message']);
        self::assertArrayHasKey('trace', $debug);
        self::assertFalse($normalizer->hasCacheableSupportsMethod());
        self::assertArrayHasKey(\Ufo\RpcError\AbstractRpcErrorException::class, $normalizer->getSupportedTypes(null));
    }

    /**
     * Перевіряє накопичення груп серіалізації, додавання сигнатури відповіді, вилучення лише батьківської
     * групи та об’єднання контекстів.
     */
    public function testContextBuilderAccumulatesGroupsAndRemovesOnlyParent(): void
    {
        $context = new RpcResponseContextBuilder();
        self::assertSame(['rpc_handle' => true], $context->toArray());
        self::assertSame(['rpc_handle' => true], $context->removeParent()->toArray());
        $response = new RpcResponse(1);
        $context = $context->withResponseSignature($response)->withGroup(['detail', 'list']);
        self::assertSame(['result', 'detail', 'list'], $context->toArray()['groups']);
        self::assertSame(['detail', 'list'], array_values($context->removeParent()->toArray()['groups']));
        $extra = (new \Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder())->withSkipNullValues(true);
        self::assertTrue($context->withContext($extra)->toArray()['skip_null_values']);
        $errorContext = (new RpcResponseContextBuilder())->withResponseSignature(new RpcResponse(1, error: new RpcError(-32600, 'bad', [])));
        self::assertSame(['error'], $errorContext->toArray()['groups']);
    }

    /**
     * Перевіряє, що білдер не змінюється: спільний екземпляр не переносить групи однієї відповіді
     * (наприклад, "error") у наступну, і DTO-результат після помилки не нормалізується як [].
     */
    public function testContextBuilderIsImmutable(): void
    {
        $shared = new RpcResponseContextBuilder();
        $error = new RpcResponse(1, error: new RpcError(-32600, 'bad', []));

        self::assertNotSame($shared, $shared->withGroup('detail'));
        self::assertNotSame($shared, $shared->removeParent());
        self::assertSame(['error'], $shared->withResponseSignature($error)->toArray()['groups']);
        self::assertSame(['rpc_handle' => true], $shared->toArray());

        $result = new RpcResponse(2, new Person('Ada', 37), contextBuilder: $shared);
        self::assertSame(['result'], $shared->withResponseSignature($result)->toArray()['groups']);
        self::assertSame(['name' => 'Ada', 'age' => 37], $result->getResult());
    }

    /**
     * Перевіряє створення успішної RPC-відповіді з JSON зі збереженням ідентифікатора й результату.
     */
    public function testResponseCreatorParsesSuccess(): void
    {
        $response = ResponseCreator::fromJson('{"id":7,"jsonrpc":"2.0","result":{"ok":true}}');
        self::assertSame(7, $response->getId());
        self::assertSame(['ok' => true], $response->getResult());
        self::assertNull($response->getError());
    }

    /**
     * Перевіряє перетворення збою неініціалізованої DTO-підсистеми на внутрішню RPC-помилку зі збереженням
     * винятку.
     */
    public function testResponseCreatorConvertsTransformationFailureToInternalError(): void
    {
        \Ufo\DTO\DTOTransformer::reset();
        try {
            $response = ResponseCreator::fromJson('{"id":7,"jsonrpc":"2.0","result":true}');
            self::assertSame(-32603, $response->getError()->getCode());
            self::assertSame('Uncatchable async error', $response->getError()->getMessage());
            self::assertInstanceOf(\Throwable::class, $response->getError()->getData());
        } finally {
            \Ufo\DTO\DTOTransformer::boot(\Ufo\DTO\Factory\DefaultDTOTransformerFactory::default()->create());
        }
    }

    /**
     * Перевіряє виняток непідтримуваного перетворення атрибута, якщо DTO-підсистема не ініціалізована.
     */
    public function testAttributeTransformerRejectsWhenDtoSubsystemIsNotInitialized(): void
    {
        \Ufo\DTO\DTOTransformer::reset();
        try {
            $this->expectException(\Ufo\DTO\Exceptions\NotSupportDTOException::class);
            AttributeTransformer::fromArray(Cache::class, []);
        } finally {
            \Ufo\DTO\DTOTransformer::boot(\Ufo\DTO\Factory\DefaultDTOTransformerFactory::default()->create());
        }
    }

    /**
     * Перевіряє збереження коду й повідомлення віддаленої помилки та ідентифікатора відповіді при читанні
     * JSON.
     */
    public function testResponseCreatorCapturesRemoteError(): void
    {
        $response = ResponseCreator::fromJson('{"id":7,"jsonrpc":"2.0","error":{"code":-32600,"message":"bad"}}');
        self::assertSame(-32600, $response->getError()->getCode());
        self::assertSame('bad', $response->getError()->getMessage());
        self::assertSame(7, $response->getId());
    }

    /**
     * Перевіряє викидання віддаленої RPC-помилки як винятку, коли її перехоплення вимкнено.
     */
    public function testResponseCreatorCanRethrowRemoteError(): void
    {
        $this->expectException(RpcBadRequestException::class);
        ResponseCreator::fromJson('{"id":7,"jsonrpc":"2.0","error":{"code":-32600,"message":"bad"}}', false);
    }

    /**
     * Перевіряє, що некоректний JSON відповіді спричиняє виняток декодування, який не приховується.
     */
    public function testResponseCreatorInvalidJsonIsNotSwallowed(): void
    {
        $this->expectException(\Symfony\Component\Serializer\Exception\NotEncodableValueException::class);
        ResponseCreator::fromJson('{');
    }

    /**
     * Перевіряє визначення підтримуваних класів атрибутів, відхилення стороннього класу та створення
     * атрибута Cache з масиву.
     */
    public function testAttributeTransformerSupportsKnownAttributes(): void
    {
        foreach (AttributeTransformer::ATTRIBUTES as $class) self::assertTrue(AttributeTransformer::isSupportClass($class));
        self::assertTrue(AttributeTransformer::isSupportClass(\Ufo\DTO\Interfaces\IArrayConstructible::class));
        self::assertFalse(AttributeTransformer::isSupportClass(Person::class));
        $cache = AttributeTransformer::fromArray(Cache::class, ['lifetimeSecond' => 42]);
        self::assertInstanceOf(Cache::class, $cache);
        self::assertSame(42, $cache->lifetimeSecond);
    }
}
