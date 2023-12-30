<?php

namespace Fabricio872\PhpLombok;

use Fabricio872\PhpLombok\Attributes\Construct;
use Override;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\String\AbstractString;
use function Symfony\Component\String\s;

class ConstructorRule extends AbstractRule
{
    private AbstractString $classData;

    #[Override] public function isApplicable(ReflectionClass $reflection): bool
    {
        foreach ($reflection->getProperties() as $property) {
            if ($this->hasAttribute($property->getAttributes(), Construct::class)) {
                return true;
            }
        }

        return false;
    }

    #[Override] public function apply(ReflectionClass $reflection, string $classData): string
    {
        $this->classData = s($classData);
        $propertiesToGenerate = [];

        foreach ($reflection->getProperties() as $property) {
            if ($this->hasAttribute($property->getAttributes(), Construct::class)) {
                $propertiesToGenerate[] = $property;
            }
        }

        if (!$reflection->hasMethod('__construct')) {
            $this->makeConstructor();
        }

        $this->updateConstructor(array_unique($propertiesToGenerate));

        return $this->classData->toString();
    }

    private function makeConstructor(): void
    {
        $constructor =
            <<<CONSTRUCTOR
    
    public function __construct()
    {
    }

CONSTRUCTOR;

        $this->classData = $this->classData->beforeLast('}')->append($constructor)->append("}\n");
    }

    private function updateConstructor(array $properties): void
    {
        preg_match_all("/function\s+(?<name>\w+)\s*\((?<param>[^\)]*)\)\s*(?<body>\{(?:[^{}]+|(?&body))*\})/", $this->classData->toString(), $matches);
        $existingParams = array_map(function (string $param) {
            return trim($param);
        }, explode(',', $matches['param'][0]));

        $params = array_map(function (ReflectionProperty $property) {
            return sprintf(
                '%s%s $%s',
                $property->getType()->allowsNull() ? '?' : '',
                $property->getType()->getName(),
                $property->getName()
            );
        }, $properties);

        $paramsCode = array_map(function (ReflectionProperty $property) {
            return sprintf(
                '$this->%s = $%s;',
                $property->getName(),
                $property->getName()
            );
        }, $properties);

        $body = s($matches['body'][0])->after('{')->beforeLast('}');

        $constructor = sprintf(
            <<<CONSTRUCTOR

    public function __construct(
    %s
    ) {
        %s
        %s
    }

CONSTRUCTOR,
            implode(",\n", array_merge($existingParams, $params)),
            $body,
            implode("\n", $paramsCode)
        );

        $existingConstructor = 'public ' . $matches[0][0];
        $this->classData = $this->classData->replace($existingConstructor, $constructor);
    }
}
