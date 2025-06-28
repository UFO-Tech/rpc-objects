<?php

namespace Ufo\RpcObject\Transformer;

use ReflectionClass;

use function preg_match;
use function trim;

class AttributeHelper
{
    public static function getMethodArgumentAttributesAsString(
        string $className,
        string $methodName,
        string $argumentName
    ): ?string
    {
        $reflectionClass = new ReflectionClass($className);
        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $fileName = $reflectionClass->getFileName();
        $startLine = $reflectionMethod->getStartLine();
        $endLine = $reflectionMethod->getEndLine();
        $fileContent = file($fileName);
        $methodContent = implode("", array_slice($fileContent, $startLine - 1, $endLine - $startLine + 1));
        $result = null;
        $matches = [];
        foreach (['RPC\\\\', ''] as $ns) {
            $pattern = '/(?<=\#\[' . $ns . 'Assertions\(\[)(?:(?!\#\[' . $ns . 'Assertions).)*?(?=\]\)\]\s*(?:\#\[[^\]]+\]\s*)*[\w\\\\|?]+\s+\$' . $argumentName . ',?)/ms';
            if (preg_match($pattern, $methodContent, $matches)) {
                $result = trim($matches[0]);
                break;
            }
        }
        return $result;
    }

}