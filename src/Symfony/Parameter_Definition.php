<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

/**
 * @api
 */
interface Parameter_Definition
{
    public function get_key(): string;
    /**
     * @return array<mixed>|bool|float|int|string
     */
    public function get_value();
}