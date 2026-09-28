<?php

namespace Nreljed\Platform\Tests;

use Nreljed\Platform\Support\ResourceDefinition;
use PHPUnit\Framework\TestCase;

final class ResourceDefinitionContractTest extends TestCase
{
    public function test_defaults_are_empty(): void
    {
        $definition = new ResourceDefinition(key: 'products', label: 'Products');

        $this->assertSame('products', $definition->getKey());
        $this->assertSame('Products', $definition->getLabel());
        $this->assertNull($definition->getSingular());
        $this->assertSame([], $definition->getTable());
        $this->assertSame([], $definition->getForm());
        $this->assertSame([], $definition->getRelations());
        $this->assertSame([], $definition->getRoutes());
    }

    public function test_to_array_round_trips_through_from_array(): void
    {
        $definition = new ResourceDefinition(
            key: 'products',
            label: 'Products',
            singular: 'Product',
            table: [
                'columns' => [
                    ['name' => 'name', 'header' => 'Name'],
                ],
            ],
            form: [
                ['title' => 'Details', 'fields' => [['name' => 'name', 'type' => 'text']]],
            ],
            relations: [
                ['key' => 'variations', 'title' => 'Variations'],
            ],
            routes: [
                'index' => 'dashboard.products.index',
                'edit' => 'dashboard.products.edit',
            ],
        );

        $this->assertSame(
            $definition->toArray(),
            ResourceDefinition::fromArray($definition->toArray())->toArray(),
            'fromArray() must reproduce a descriptor exactly: getAdminResources() caches these for the memoised registry.',
        );
    }

    public function test_to_array_omits_nothing_and_adds_nothing(): void
    {
        $definition = new ResourceDefinition(
            key: 'products',
            label: 'Products',
            singular: 'Product',
            table: ['columns' => [['name' => 'id', 'header' => 'ID']]],
        );

        $this->assertSame(
            ['key', 'label', 'singular', 'table', 'form', 'relations', 'routes'],
            array_keys($definition->toArray()),
        );
    }

    public function test_from_array_falls_back_to_empty_values(): void
    {
        $definition = ResourceDefinition::fromArray([]);

        $this->assertSame('', $definition->getKey());
        $this->assertSame('', $definition->getLabel());
        $this->assertNull($definition->getSingular());
        $this->assertSame([], $definition->getRoutes());
    }

    public function test_the_generator_output_is_schema_valid(): void
    {
        $definition = new ResourceDefinition(
            key: 'products',
            label: 'Products',
            singular: 'Product',
            table: [
                'columns' => [
                    ['name' => 'id', 'header' => 'ID'],
                    ['name' => 'name', 'header' => 'Name', 'sortable' => true],
                ],
                'searchKey' => 'name',
                'filters' => [
                    ['key' => 'status', 'options' => [['label' => 'Active', 'value' => 'active']]],
                ],
            ],
            form: [
                ['title' => 'Details', 'fields' => [
                    ['name' => 'name', 'type' => 'text', 'label' => 'Name', 'required' => true],
                    ['name' => 'price', 'type' => 'money', 'colSpan' => 2],
                ]],
            ],
            relations: [
                ['key' => 'variations', 'title' => 'Variations'],
            ],
            routes: [
                'index' => 'dashboard.products.index',
                'create' => 'dashboard.products.create',
                'store' => 'dashboard.products.store',
                'edit' => 'dashboard.products.edit',
                'update' => 'dashboard.products.update',
                'destroy' => 'dashboard.products.destroy',
            ],
        );

        $this->assertSame([], $definition->violations());
        $definition->assertValid('shop-module');
    }

    public function test_key_and_label_are_mandatory(): void
    {
        $definition = new ResourceDefinition(key: '', label: 'Products');

        $this->assertContains('key must be a non-empty string', $definition->violations());

        $definition = new ResourceDefinition(key: 'products', label: '');

        $this->assertContains('label must be a non-empty string', $definition->violations());
    }

    public function test_ts_closure_surfaces_are_rejected(): void
    {
        $definition = new ResourceDefinition(
            key: 'products',
            label: 'Products',
            form: [
                ['fields' => [['name' => 'sku', 'type' => 'text', 'serialize' => fn () => null]]],
            ],
            table: [
                'bulkActions' => [['label' => 'Delete']],
                'columns' => [['name' => 'id', 'header' => 'ID', 'cell' => fn () => 'x']],
            ],
            relations: [
                ['key' => 'variations', 'component' => 'VariationsManager'],
            ],
        );

        $violations = $definition->violations();

        $joined = implode("\n", $violations);

        $this->assertStringContainsString('table.bulkActions is not expressible in a PHP descriptor', $joined);
        $this->assertStringContainsString('table.columns[0].cell is not expressible in a PHP descriptor', $joined);
        $this->assertStringContainsString('form.0.fields[0].serialize is not expressible in a PHP descriptor', $joined);
        $this->assertStringContainsString('relations[0].component is not expressible in a PHP descriptor', $joined);
    }

    public function test_unknown_field_type_is_rejected(): void
    {
        $definition = new ResourceDefinition(
            key: 'products',
            label: 'Products',
            form: [
                ['fields' => [['name' => 'swatch', 'type' => 'colour-swatch']]],
            ],
        );

        $this->assertStringContainsString(
            'form.0.fields[0].type must be one of [text|textarea|number|money|select|boolean|date|datetime|slug|json|media|password|hidden]',
            implode("\n", $definition->violations()),
        );
    }

    public function test_dynamic_has_no_built_in_control_and_is_rejected_for_php_descriptors(): void
    {
        $definition = new ResourceDefinition(
            key: 'products',
            label: 'Products',
            form: [
                ['fields' => [['name' => 'tail', 'type' => 'dynamic']]],
            ],
        );

        $this->assertStringContainsString(
            'form.0.fields[0].type must be one of [text|textarea|number|money|select|boolean|date|datetime|slug|json|media|password|hidden]',
            implode("\n", $definition->violations()),
        );
    }

    public function test_column_without_name_and_route_that_is_not_a_route_name_are_rejected(): void
    {
        $definition = new ResourceDefinition(
            key: 'products',
            label: 'Products',
            table: ['columns' => [['header' => 'ID']]],
            routes: ['edit' => 42, 'rename' => 'dashboard.products.rename'],
        );

        $violations = $definition->violations();

        $this->assertContains('table.columns[0].name must be a non-empty accessor key', $violations);
        $this->assertContains('routes.edit must be a non-empty route name (string), got int', $violations);
        $this->assertContains('routes declares unknown slot [rename]', $violations);
    }

    public function test_assert_valid_attributes_the_owner_slug(): void
    {
        $definition = new ResourceDefinition(key: 'products', label: 'Products', relations: [['title' => 'Variations']]);

        try {
            $definition->assertValid('shop-module');
            $this->fail('assertValid() must throw for a malformed descriptor.');
        } catch (\InvalidArgumentException $exception) {
            $this->assertStringContainsString(
                'Feature [shop-module] declared resource [products]',
                $exception->getMessage(),
            );
            $this->assertStringContainsString('relations[0].key must be a non-empty string', $exception->getMessage());
        }
    }
}
