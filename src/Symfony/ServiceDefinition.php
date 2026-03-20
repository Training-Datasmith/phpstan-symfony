<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

/**
 * @api
 */
interface Service_Definition
{
    public function get_id(): string;
    public function get_class(): ?string;
    public function is_public(): bool;
    public function is_synthetic(): bool;
    public function get_alias(): ?string;
    /** @return ServiceTag[] */
    public function get_tags(): array;
}