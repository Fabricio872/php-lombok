<?php

namespace Fabricio872\PhpLombok\Attributes;

use Attribute;

#[Attribute(Attribute::TARGET_CLASS_CONSTANT|Attribute::TARGET_PROPERTY)]
class Getter
{
    public function __construct(
        public bool $isFluent = true
    )
    {
    }
}