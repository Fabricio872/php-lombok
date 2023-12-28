<?php
declare(strict_types=1);

namespace Fabricio872\PhpLombok;

use Fabricio872\PhpCompiler\Rules\RuleInterface;
use Fabricio872\PhpLombok\Attributes\Getter;
use Fabricio872\PhpLombok\Attributes\NoGetter;
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
        if ($this->hasAttribute($reflection->getAttributes())) {
            return true;
        }

        foreach ($reflection->getProperties() as $property) {
            if ($this->hasAttribute($property->getAttributes())) {
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
        if ($this->hasAttribute($reflection->getAttributes())) {
            $propertiesToGenerate = $reflection->getProperties();
        }

        foreach ($reflection->getProperties() as $property) {
            if ($this->hasAttribute($property->getAttributes())) {
                $propertiesToGenerate[] = $property;
            }
        }
        $gettersString = "";

        /** @var ReflectionProperty $propertyToGenerate */
        foreach (array_unique($propertiesToGenerate) as $propertyToGenerate) {
            if (
                !$this->getterExists($reflection, $propertyToGenerate) &&
                !$this->hasAttribute($propertyToGenerate->getAttributes(), NoGetter::class)
            ) {
                $gettersString .= $this->generateGetter($propertyToGenerate);
            }
        }

        return $classData->beforeLast('}')->append($gettersString)->append("}\n")->toString();
    }

    /**
     * @param array<int, ReflectionAttribute> $attributes
     * @return bool
     */
    private function hasAttribute(array $attributes, string $attributeClass = Getter::class)
    {
        foreach ($attributes as $attribute) {
            if ($attribute->getName() == $attributeClass) {
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

    private function getterExists(ReflectionClass $class, ReflectionProperty $property): bool
    {
        return $class->hasMethod(sprintf("get%s", s($property->getName())->title()));
    }
}
