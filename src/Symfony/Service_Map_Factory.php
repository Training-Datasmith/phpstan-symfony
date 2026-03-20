<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

interface Service_Map_Factory
{
    public function create(): Service_Map;
}