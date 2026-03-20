# Architecture: phpstan-symfony

## Purpose

A PHPStan extension providing static analysis support for Symfony framework applications.
It adds accurate return types for container/controller methods, detects unknown or private
service access, infers console command argument types, and supports Messenger, Serializer,
Form, and Configuration tree patterns.

## Directory Structure

```
src/
  Rules/Symfony/
    Container_Interface_Private_Service_Rule.php   # Error: accessing a private service via get()
    Container_Interface_Unknown_Service_Rule.php   # Error: accessing an unregistered service
    Invalid_Argument_Default_Value_Rule.php        # Error: wrong default value type for console argument
    Invalid_Option_Default_Value_Rule.php          # Error: wrong default value type for console option
    Undefined_Argument_Rule.php                    # Error: referencing undefined console argument
    Undefined_Option_Rule.php                      # Error: referencing undefined console option
  Symfony/
    Console_Application_Resolver.php   # Loads the Symfony console app for command introspection
    Default_Parameter_Map.php          # ParameterMap backed by container XML
    Default_Service_Map.php            # ServiceMap backed by container XML
    Fake_Parameter_Map.php             # No-op fallback when no container XML is configured
    Fake_Service_Map.php               # No-op fallback for service map
    Input_Bag_Stub_Files_Extension.php # Selects the correct InputBag stub for the Symfony version
    Message_Map.php                    # Maps Messenger message class → handler return type
    Message_Map_Factory.php            # Builds MessageMap from the container XML
    Parameter.php                      # Value object for a container parameter
    Parameter_Definition.php           # Value object for a parameter definition
    Parameter_Map.php                  # @api interface for parameter lookups
    Parameter_Map_Factory.php          # Builds ParameterMap from the container XML
    Required_Autowiring_Extension.php  # Detects @required properties not set via autowiring
    Service.php                        # Value object for a container service
    Service_Definition.php             # Value object for a service definition (id, class, tags, etc.)
    Service_Map.php                    # @api interface for service lookups
    Service_Map_Factory.php            # Builds ServiceMap by parsing the container XML dump
    Service_Tag.php / Service_Tag_Definition.php  # Value objects for service tags
    Symfony_Container_Result_Cache_Meta_Extension.php  # Invalidates PHPStan cache on container XML change
  Type/Symfony/
    (dynamic return type extensions for container, request, form, serializer, console, etc.)
stubs/              # PHPStan stub files for Symfony and PSR-* classes
extension.neon      # Auto-loaded via extension-installer; registers type extensions, stubs, services
rules.neon          # Registers the 6 Symfony-specific rules
```

## Key Design Decisions

### Container XML as the Source of Truth

Rather than analyzing the DI configuration at analysis time, the extension reads the
pre-compiled container XML dump (`containerXmlPath`). This is fast and reliable, but
requires the container to be compiled before PHPStan runs. `Default_Service_Map` and
`Default_Parameter_Map` both parse this XML.

### Fake Maps as Fallbacks

When `containerXmlPath` is not configured, `Fake_Service_Map` and `Fake_Parameter_Map`
provide no-op implementations that return empty results, allowing the extension to work
safely without a container XML. Rules check for the fake implementations and skip accordingly.

### Type Extensions vs Rules

- **Type extensions** (`src/Type/Symfony/`) — Change inferred return types; make Symfony's
  dynamic methods type-safe for PHPStan.
- **Rules** (`src/Rules/Symfony/`) — Report actionable errors for incorrect usage patterns.

### Cache Invalidation

`Symfony_Container_Result_Cache_Meta_Extension` watches the container XML file and
instructs PHPStan to invalidate its result cache when the file changes, ensuring that
service map updates are reflected without a manual cache clear.

## Extension Points

- **`Service_Map`** (`@api`) — Implement to provide services from a custom source.
- **`Parameter_Map`** (`@api`) — Implement to provide parameters from a custom source.
- Configure `consoleApplicationLoader` to enable console argument/option type inference.

## Dependency Flow

```
extension.neon + rules.neon
  ├─ Service_Map_Factory → Default_Service_Map (parses container XML)
  ├─ Parameter_Map_Factory → Default_Parameter_Map
  ├─ Message_Map_Factory → Message_Map
  ├─ Console_Application_Resolver (optional — loaded via consoleApplicationLoader path)
  └─ Type/Symfony/* extensions → Service_Map, Parameter_Map, Message_Map
```
