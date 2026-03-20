<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

use function count;
use function file_get_contents;
use function ksort;
use function simplexml_load_string;
use Simple_Xml_Element;
use function sprintf;
use function strpos;
use function substr;
final class Xml_Service_Map_Factory implements Service_Map_Factory
{
    private ?string $container_xml = null;
    public function __construct(?string $container_xml_path)
    {
        $this->container_xml = $container_xml_path;
    }
    public function create(): Service_Map
    {
        if ($this->container_xml === null) {
            return new Fake_Service_Map();
        }
        $file_contents = file_get_contents($this->container_xml);
        if ($file_contents === false) {
            throw new Xml_Container_Not_Exists_Exception(sprintf('Container %s does not exist', $this->container_xml));
        }
        $xml = @simplexml_load_string($file_contents);
        if ($xml === false) {
            throw new Xml_Container_Not_Exists_Exception(sprintf('Container %s cannot be parsed', $this->container_xml));
        }
        /** @var Service[] $services */
        $services = [];
        /** @var Service[] $aliases */
        $aliases = [];
        if (count($xml->services) > 0) {
            foreach ($xml->services->service as $def) {
                /** @var SimpleXMLElement $attrs */
                $attrs = $def->attributes();
                if (!isset($attrs->id)) {
                    continue;
                }
                $service_tags = [];
                foreach ($def->tag as $tag) {
                    $tag_attrs = ((array) $tag->attributes())['@attributes'] ?? [];
                    $tag_name = $tag_attrs['name'];
                    unset($tag_attrs['name']);
                    $service_tags[] = new Service_Tag($tag_name, $tag_attrs);
                }
                $service = new Service($this->clean_service_id((string) $attrs->id), isset($attrs->class) ? (string) $attrs->class : null, isset($attrs->public) && (string) $attrs->public === 'true', isset($attrs->synthetic) && (string) $attrs->synthetic === 'true', isset($attrs->alias) ? $this->clean_service_id((string) $attrs->alias) : null, $service_tags);
                if ($service->get_alias() !== null) {
                    $aliases[] = $service;
                } else {
                    $services[$service->get_id()] = $service;
                }
            }
        }
        foreach ($aliases as $service) {
            $alias = $service->get_alias();
            if ($alias === null) {
                continue;
            }
            if (!isset($services[$alias])) {
                continue;
            }
            $id = $service->get_id();
            $services[$id] = new Service($id, $services[$alias]->get_class(), $service->is_public(), $service->is_synthetic(), $alias);
        }
        ksort($services);
        return new Default_Service_Map($services);
    }
    private function clean_service_id(string $id): string
    {
        return strpos($id, '.') === 0 ? substr($id, 1) : $id;
    }
}