<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

use Php_Stan\Better_Reflection\Reflector\Exception\Identifier_Not_Found;
use Php_Stan\Better_Reflection\Reflector\Reflector;
use Php_Stan\Php_Doc\Stub_Files_Extension;
class Input_Bag_Stub_Files_Extension implements Stub_Files_Extension
{
    private Reflector $reflector;
    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
    }
    public function get_files(): array
    {
        try {
            $this->reflector->reflect_class('Symfony\Component\HttpFoundation\InputBag');
        } catch (Identifier_Not_Found $e) {
            return [];
        }
        return [__DIR__ . '/../../stubs/Symfony/Component/HttpFoundation/InputBag.stub', __DIR__ . '/../../stubs/Symfony/Component/HttpFoundation/Request.stub'];
    }
}