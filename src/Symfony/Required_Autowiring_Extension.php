<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

use function count;
use Php_Stan\Reflection\Additional_Constructors_Extension;
use Php_Stan\Reflection\Class_Reflection;
use Php_Stan\Reflection\Php\Php_Property_Reflection;
use Php_Stan\Reflection\Property_Reflection;
use Php_Stan\Rules\Properties\Read_Write_Properties_Extension;
use Php_Stan\Type\File_Type_Mapper;
class Required_Autowiring_Extension implements Read_Write_Properties_Extension, Additional_Constructors_Extension
{
    private File_Type_Mapper $file_type_mapper;
    public function __construct(File_Type_Mapper $file_type_mapper)
    {
        $this->file_type_mapper = $file_type_mapper;
    }
    public function is_always_read(Property_Reflection $property, string $property_name): bool
    {
        return false;
    }
    public function is_always_written(Property_Reflection $property, string $property_name): bool
    {
        return false;
    }
    public function is_initialized(Property_Reflection $property, string $property_name): bool
    {
        // If the property is public, check for @required on the property itself
        if (!$property->is_public()) {
            return false;
        }
        $declaring_class = $property->get_declaring_class();
        $declaring_trait = null;
        if ($property instanceof Php_Property_Reflection && $property->get_declaring_trait() !== null) {
            $declaring_trait = $property->get_declaring_trait()->get_name();
        }
        if ($property->get_doc_comment() !== null && $declaring_class->get_file_name() !== null && $this->is_required_from_doc_comment($declaring_class->get_file_name(), $declaring_class->get_name(), $declaring_trait, null, $property->get_doc_comment())) {
            return true;
        }
        // Check for the attribute version
        if ($property instanceof Php_Property_Reflection && count($property->get_native_reflection()->get_attributes(\Symfony\Contracts\Service\Attribute\Required::class)) > 0) {
            return true;
        }
        return false;
    }
    public function get_additional_constructors(Class_Reflection $class_reflection): array
    {
        $additional_constructors = [];
        $native_reflection = $class_reflection->get_native_reflection();
        foreach ($native_reflection->get_better_reflection()->get_methods() as $method) {
            if (!$method->is_public()) {
                continue;
            }
            if ($method->get_implementing_class()->get_name() !== $native_reflection->get_name()) {
                continue;
            }
            $declaring_trait = null;
            if ($method->get_declaring_class()->is_trait()) {
                $declaring_trait = $method->get_declaring_class()->get_name();
            }
            if ($method->get_doc_comment() !== null && $method->get_file_name() !== null && $this->is_required_from_doc_comment($method->get_file_name(), $native_reflection->get_name(), $declaring_trait, $method->get_name(), $method->get_doc_comment())) {
                $additional_constructors[] = $method->get_name();
            }
            if (count($method->get_attributes_by_name(\Symfony\Contracts\Service\Attribute\Required::class)) === 0) {
                continue;
            }
            $additional_constructors[] = $method->get_name();
        }
        return $additional_constructors;
    }
    private function is_required_from_doc_comment(string $file_name, string $class_name, ?string $trait_name, ?string $function_name, string $doc_comment): bool
    {
        $php_doc = $this->file_type_mapper->get_resolved_php_doc($file_name, $class_name, $trait_name, $function_name, $doc_comment);
        foreach ($php_doc->get_php_doc_nodes() as $node) {
            // @required tag is available, meaning this property is always initialized
            if (count($node->get_tags_by_name('@required')) > 0) {
                return true;
            }
        }
        return false;
    }
}