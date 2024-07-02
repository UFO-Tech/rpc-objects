<?php

namespace Ufo\RpcObject\Transformer;

use ReflectionClass;

use function preg_match;
use function trim;

class AttributeHelper
{
    public static function getMethodArgumentAttributesAsString(string $className, string $methodName, string $argumentName): ?string
    {
        $reflectionClass = new ReflectionClass($className);
        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $fileName = $reflectionClass->getFileName();
        $startLine = $reflectionMethod->getStartLine();
        $endLine = $reflectionMethod->getEndLine();

        $fileContent = file($fileName);
        $methodContent = implode("", array_slice($fileContent, $startLine - 1, $endLine - $startLine + 1));

        preg_match(
            '/(?<=\#\[RPC\\\\Assertions\(\[)[\w\s_\\\\(\),"\'=>\[\]]*\s?(?=\]\)\]\s*[\w?|]+\s\$'.$argumentName.',?)/m',
            $methodContent,
            $matches
        );
        $result = $matches[0] ?? '';
        return trim($result);
    }
}