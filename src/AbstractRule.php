<?php

namespace Fabricio872\PhpLombok;

use Fabricio872\PhpCompiler\Rules\RuleInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use function Symfony\Component\String\s;

abstract class AbstractRule implements RuleInterface
{
    /**
     * @param array<int, ReflectionAttribute> $attributes
     * @param string $attributeClass
     * @return false|object
     */
    protected function hasAttribute(array $attributes, string $attributeClass): false|object
    {
        foreach ($attributes as $attribute) {
            if ($attribute->getName() == $attributeClass) {
                return $attribute->newInstance();
            }
        }
        return false;
    }

    protected function getterExists(ReflectionClass $class, ReflectionProperty $property): bool
    {
        return $class->hasMethod(sprintf("get%s", s($property->getName())->title()));
    }
}
