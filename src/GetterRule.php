<?php

namespace Fabricio872\PhpLombok;

use Fabricio872\PhpCompiler\Rules\RuleInterface;
use Fabricio872\PhpLombok\Attributes\Getter;
use Override;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use function Symfony\Component\String\s;

class GetterRule implements RuleInterface
{

    #[Override]
    public function isApplicable(ReflectionClass $reflection): bool
    {
        if ($this->isAttributeCorrect($reflection->getAttributes())) {
            return true;
        }

        foreach ($reflection->getProperties() as $property) {
            if ($this->isAttributeCorrect($property->getAttributes())) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    public function apply(ReflectionClass $reflection, string $classData): string
    {
        $classData = s($classData);
        $propertiesToGenerate = [];
        if ($this->isAttributeCorrect($reflection->getAttributes())) {
            $propertiesToGenerate = $reflection->getProperties();
        }

        foreach ($reflection->getProperties() as $property) {
            if ($this->isAttributeCorrect($property->getAttributes())) {
                $propertiesToGenerate[] = $property;
            }
        }
        $gettersString = "";

        foreach (array_unique($propertiesToGenerate) as $propertyToGenerate) {
            $gettersString .= $this->generateGetter($propertyToGenerate);
        }

        return $classData->beforeLast('}')->append($gettersString)->append("}\n");
    }

    /**
     * @param array<int, ReflectionAttribute> $attributes
     * @return bool
     */
    private function isAttributeCorrect(array $attributes)
    {
        foreach ($attributes as $attribute) {
            if ($attribute->getName() == Getter::class) {
                return true;
            }
        }
        return false;
    }

    private function generateGetter(ReflectionProperty $property): string
    {
        $name = $property->getName();
        $type = $property->getType() ? sprintf(': %s', $property->getType()->getName()) : '';

        return sprintf(
            <<<GETTER

    public function get%s()%s
    {
        return \$this->%s;
    }

GETTER
            , s($name)->title(), $type, $name
        );
    }
}
