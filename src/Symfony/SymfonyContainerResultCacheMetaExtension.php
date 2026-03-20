<?php

declare (strict_types=1);
namespace Php_Stan\Symfony;

use function array_map;
use function hash;
use function ksort;
use Php_Stan\Analyser\Result_Cache\Result_Cache_Meta_Extension;
use function sort;
use function var_export;
final class Symfony_Container_Result_Cache_Meta_Extension implements Result_Cache_Meta_Extension
{
    private Parameter_Map $parameter_map;
    private Service_Map $service_map;
    public function __construct(Parameter_Map $parameter_map, Service_Map $service_map)
    {
        $this->parameter_map = $parameter_map;
        $this->service_map = $service_map;
    }
    public function get_key(): string
    {
        return 'symfonyDiContainer';
    }
    public function get_hash(): string
    {
        $services = $parameters = [];
        foreach ($this->parameter_map->get_parameters() as $parameter) {
            $parameters[$parameter->get_key()] = $parameter->get_value();
        }
        ksort($parameters);
        foreach ($this->service_map->get_services() as $service) {
            $service_tags = array_map(static fn(Service_Tag $tag): array => ['name' => $tag->get_name(), 'attributes' => $tag->get_attributes()], $service->get_tags());
            sort($service_tags);
            $services[$service->get_id()] = ['class' => $service->get_class(), 'public' => $service->is_public() ? 'yes' : 'no', 'synthetic' => $service->is_synthetic() ? 'yes' : 'no', 'alias' => $service->get_alias(), 'tags' => $service_tags];
        }
        ksort($services);
        return hash('sha256', var_export(['parameters' => $parameters, 'services' => $services], true));
    }
}