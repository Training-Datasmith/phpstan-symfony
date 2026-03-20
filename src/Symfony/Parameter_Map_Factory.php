<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

interface Parameter_Map_Factory
{
    public function create(): Parameter_Map;
}