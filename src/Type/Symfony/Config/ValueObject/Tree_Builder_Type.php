<?php

declare (strict_types=1);
namespace Php_Stan\Type\Symfony\Config\Value_Object;

use Php_Stan\Type\Object_Type;
class Tree_Builder_Type extends Object_Type
{
    private string $root_node_class_name;
    public function __construct(string $class_name, string $root_node_class_name)
    {
        parent::__construct($class_name);
        $this->root_node_class_name = $root_node_class_name;
    }
    public function get_root_node_class_name(): string
    {
        return $this->root_node_class_name;
    }
    protected function describe_additional_cache_key(): string
    {
        return $this->get_root_node_class_name();
    }
}