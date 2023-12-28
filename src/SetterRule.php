<?php

namespace Fabricio872\PhpLombok;

use Fabricio872\PhpLombok\Attributes\NoSetter;
use Fabricio872\PhpLombok\Attributes\Setter;
use Override;
use ReflectionClass;
use ReflectionProperty;
use function Symfony\Component\String\s;

class SetterRule extends AbstractRule
{

    #[Override]
    public function isApplicable(ReflectionClass $reflection): bool
    {
        if ($this->hasAttribute($reflection->getAttributes(), Setter::class)) {
            return true;
        }

        foreach ($reflection->getProperties() as $property) {
            if ($this->hasAttribute($property->getAttributes(), Setter::class)) {
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
        if ($classAttribute = $this->hasAttribute($reflection->getAttributes(), Setter::class)) {
            $propertiesToGenerate = $reflection->getProperties();
        }

        foreach ($reflection->getProperties() as $property) {
            if ($this->hasAttribute($property->getAttributes(), Setter::class)) {
                $propertiesToGenerate[] = $property;
            }
        }
        $gettersString = "";

        /** @var ReflectionProperty $propertyToGenerate */
        foreach (array_unique($propertiesToGenerate) as $propertyToGenerate) {
            if (
                !$this->getterExists($reflection, $propertyToGenerate) &&
                !$this->hasAttribute($propertyToGenerate->getAttributes(), NoSetter::class)
            ) {
                $gettersString .= $this->generateSetter(
                    $propertyToGenerate,
                    $this->hasAttribute(
                        $propertyToGenerate->getAttributes(),
                        Setter::class
                    ) ? $this->hasAttribute(
                        $propertyToGenerate->getAttributes(),
                        Setter::class
                    )->isFluent : $classAttribute->isFluent
                );
            }
        }

        return $classData->beforeLast('}')->append($gettersString)->append("}\n");
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
