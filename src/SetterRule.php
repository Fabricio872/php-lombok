<?php

namespace Fabricio872\PhpLombok;

use Attribute;
use Fabricio872\PhpCompiler\Rules\RuleInterface;
use Fabricio872\PhpLombok\Attributes\Getter;
use Fabricio872\PhpLombok\Attributes\Setter;
use Override;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionProperty;
use function Symfony\Component\String\s;

class SetterRule implements RuleInterface
{

    #[Override]
    public function isApplicable(ReflectionClass $reflection): bool
    {
        if ($this->isAttribute($reflection->getAttributes())) {
            return true;
        }

        foreach ($reflection->getProperties() as $property) {
            if ($this->isAttribute($property->getAttributes())) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    public function apply(ReflectionClass $reflection, string $classData): string
    {
        $classData = s($classData);
        /** @var array<int, ReflectionProperty> $propertiesToGenerate */
        $propertiesToGenerate = [];
        if ($classAttribute = $this->isAttribute($reflection->getAttributes())) {
            $propertiesToGenerate = $reflection->getProperties();
        }

        foreach ($reflection->getProperties() as $property) {
            if ($this->isAttribute($property->getAttributes())) {
                $propertiesToGenerate[] = $property;
            }
        }
        $gettersString = "";

        /** @var ReflectionProperty $propertyToGenerate */
        foreach (array_unique($propertiesToGenerate) as $propertyToGenerate) {
            $gettersString .= $this->generateSetter(
                $propertyToGenerate,
                $this->isAttribute($propertyToGenerate->getAttributes()) ? $this->isAttribute($propertyToGenerate->getAttributes())->isFluent : $classAttribute->isFluent
            );
        }

        return $classData->beforeLast('}')->append($gettersString)->append("}\n");
    }

    /**
     * @param array<int, ReflectionAttribute> $attributes
     * @return false|Setter
     */
    private function isAttribute(array $attributes): false|Setter
    {
        foreach ($attributes as $attribute) {
            if ($attribute->getName() == Setter::class) {
                return $attribute->newInstance();
            }
        }
        return false;
    }

    private function generateSetter(ReflectionProperty $property, bool $isFluent): string
    {
        $name = $property->getName();
        $type = $property->getType() ? $property->getType()->getName() : '';

        if (!$isFluent) {
            return sprintf(
                <<<SETTER

    public function set%s(%s \$%s): void
    {
        \$this->%s = \$%s;
    }

SETTER
                , s($name)->title(), $type, $name, $name, $name
            );
        } else {
            return sprintf(
                <<<SETTER_FLUENT

    public function set%s(%s \$%s): self
    {
        \$this->%s = \$%s;
        
        return \$this;
    }

SETTER_FLUENT
                , s($name)->title(), $type, $name, $name, $name
            );
        }
    }
}
