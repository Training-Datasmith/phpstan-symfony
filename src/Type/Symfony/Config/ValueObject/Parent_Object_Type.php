<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony\Config\Value_Object;

use Php_Stan\Type\Object_Type;
use Php_Stan\Type\Type;
use Php_Stan\Type\Verbosity_Level;
class Parent_Object_Type extends Object_Type
{
    private Type $parent;
    public function __construct(string $class_name, Type $parent)
    {
        parent::__construct($class_name);
        $this->parent = $parent;
    }
    public function get_parent(): Type
    {
        return $this->parent;
    }
    protected function describe_additional_cache_key(): string
    {
        return $this->parent->describe(Verbosity_Level::cache());
    }
}