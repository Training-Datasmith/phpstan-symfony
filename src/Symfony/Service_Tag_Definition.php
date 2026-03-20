<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

interface Service_Tag_Definition
{
    public function get_name(): string;
    /** @return array<string, string> */
    public function get_attributes(): array;
}