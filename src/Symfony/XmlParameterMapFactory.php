<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

use function base64_decode;
use function count;
use function file_get_contents;
use InvalidArgumentException;
use function is_numeric;
use function ksort;
use Php_Stan\Should_Not_Happen_Exception;
use function simplexml_load_string;
use Simple_Xml_Element;
use function sprintf;
use function strpos;
final class Xml_Parameter_Map_Factory implements Parameter_Map_Factory
{
    private ?string $container_xml = null;
    public function __construct(?string $container_xml_path)
    {
        $this->container_xml = $container_xml_path;
    }
    public function create(): Parameter_Map
    {
        if ($this->container_xml === null) {
            return new Fake_Parameter_Map();
        }
        $file_contents = file_get_contents($this->container_xml);
        if ($file_contents === false) {
            throw new Xml_Container_Not_Exists_Exception(sprintf('Container %s does not exist', $this->container_xml));
        }
        $xml = @simplexml_load_string($file_contents);
        if ($xml === false) {
            throw new Xml_Container_Not_Exists_Exception(sprintf('Container %s cannot be parsed', $this->container_xml));
        }
        /** @var Parameter[] $parameters */
        $parameters = [];
        if (count($xml->parameters) > 0) {
            foreach ($xml->parameters->parameter as $def) {
                /** @var SimpleXMLElement $attrs */
                $attrs = $def->attributes();
                $parameter = new Parameter((string) $attrs->key, $this->get_node_value($def));
                $parameters[$parameter->get_key()] = $parameter;
            }
        }
        ksort($parameters);
        return new Default_Parameter_Map($parameters);
    }
    /**
     * @return array<mixed>|bool|float|int|string
     */
    private function get_node_value(Simple_Xml_Element $def)
    {
        /** @var SimpleXMLElement $attrs */
        $attrs = $def->attributes();
        $value = null;
        switch ((string) $attrs->type) {
            case 'collection':
                $value = [];
                $children = $def->children();
                if ($children === null) {
                    throw new Should_Not_Happen_Exception();
                }
                foreach ($children as $child) {
                    /** @var SimpleXMLElement $childAttrs */
                    $child_attrs = $child->attributes();
                    if (isset($child_attrs->key)) {
                        $value[(string) $child_attrs->key] = $this->get_node_value($child);
                    } else {
                        $value[] = $this->get_node_value($child);
                    }
                }
                break;
            case 'string':
                $value = (string) $def;
                break;
            case 'binary':
                $value = base64_decode((string) $def, true);
                if ($value === false) {
                    throw new InvalidArgumentException(sprintf('Parameter "%s" of binary type is not valid base64 encoded string.', (string) $attrs->key));
                }
                break;
            default:
                $value = (string) $def;
                if (is_numeric($value)) {
                    if (strpos($value, '.') !== false) {
                        $value = (float) $value;
                    } else {
                        $value = (int) $value;
                    }
                } elseif ($value === 'true') {
                    $value = true;
                } elseif ($value === 'false') {
                    $value = false;
                }
        }
        return $value;
    }
}