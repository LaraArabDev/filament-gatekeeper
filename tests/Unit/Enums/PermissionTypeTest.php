<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Enums;

use LaraArabDev\FilamentGatekeeper\Enums\PermissionType;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class PermissionTypeTest extends TestCase
{
    /** @test */
    public function it_has_all_eight_cases(): void
    {
        $cases = PermissionType::cases();

        $this->assertCount(8, $cases);

        $values = array_map(fn ($case) => $case->value, $cases);

        $this->assertContains('resource', $values);
        $this->assertContains('model', $values);
        $this->assertContains('page', $values);
        $this->assertContains('widget', $values);
        $this->assertContains('field', $values);
        $this->assertContains('column', $values);
        $this->assertContains('action', $values);
        $this->assertContains('relation', $values);
    }

    /** @test */
    public function it_can_be_created_from_string_value(): void
    {
        $this->assertEquals(PermissionType::Resource, PermissionType::from('resource'));
        $this->assertEquals(PermissionType::Model, PermissionType::from('model'));
        $this->assertEquals(PermissionType::Page, PermissionType::from('page'));
        $this->assertEquals(PermissionType::Widget, PermissionType::from('widget'));
        $this->assertEquals(PermissionType::Field, PermissionType::from('field'));
        $this->assertEquals(PermissionType::Column, PermissionType::from('column'));
        $this->assertEquals(PermissionType::Action, PermissionType::from('action'));
        $this->assertEquals(PermissionType::Relation, PermissionType::from('relation'));
    }

    /** @test */
    public function it_returns_correct_labels(): void
    {
        $this->assertEquals('Resource', PermissionType::Resource->getLabel());
        $this->assertEquals('Model', PermissionType::Model->getLabel());
        $this->assertEquals('Page', PermissionType::Page->getLabel());
        $this->assertEquals('Widget', PermissionType::Widget->getLabel());
        $this->assertEquals('Field', PermissionType::Field->getLabel());
        $this->assertEquals('Column', PermissionType::Column->getLabel());
        $this->assertEquals('Action', PermissionType::Action->getLabel());
        $this->assertEquals('Relation', PermissionType::Relation->getLabel());
    }

    /** @test */
    public function it_returns_correct_colors(): void
    {
        $this->assertEquals('primary', PermissionType::Resource->getColor());
        $this->assertEquals('cyan', PermissionType::Model->getColor());
        $this->assertEquals('success', PermissionType::Page->getColor());
        $this->assertEquals('warning', PermissionType::Widget->getColor());
        $this->assertEquals('info', PermissionType::Field->getColor());
        $this->assertEquals('gray', PermissionType::Column->getColor());
        $this->assertEquals('danger', PermissionType::Action->getColor());
        $this->assertEquals('purple', PermissionType::Relation->getColor());
    }

    /** @test */
    public function it_returns_correct_icons(): void
    {
        $this->assertEquals('heroicon-o-rectangle-stack', PermissionType::Resource->getIcon());
        $this->assertEquals('heroicon-o-cube', PermissionType::Model->getIcon());
        $this->assertEquals('heroicon-o-document', PermissionType::Page->getIcon());
        $this->assertEquals('heroicon-o-chart-bar', PermissionType::Widget->getIcon());
        $this->assertEquals('heroicon-o-pencil-square', PermissionType::Field->getIcon());
        $this->assertEquals('heroicon-o-view-columns', PermissionType::Column->getIcon());
        $this->assertEquals('heroicon-o-bolt', PermissionType::Action->getIcon());
        $this->assertEquals('heroicon-o-link', PermissionType::Relation->getIcon());
    }

    /** @test */
    public function it_returns_options_for_select(): void
    {
        $options = PermissionType::optionsForSelect();

        $this->assertIsArray($options);
        $this->assertCount(8, $options);

        $this->assertEquals('Resource', $options['resource']);
        $this->assertEquals('Model', $options['model']);
        $this->assertEquals('Page', $options['page']);
        $this->assertEquals('Widget', $options['widget']);
        $this->assertEquals('Field', $options['field']);
        $this->assertEquals('Column', $options['column']);
        $this->assertEquals('Action', $options['action']);
        $this->assertEquals('Relation', $options['relation']);
    }

    /** @test */
    public function it_options_keys_match_case_values(): void
    {
        $options = PermissionType::optionsForSelect();

        foreach (PermissionType::cases() as $case) {
            $this->assertArrayHasKey($case->value, $options);
            $this->assertEquals($case->getLabel(), $options[$case->value]);
        }
    }

    /** @test */
    public function it_try_from_returns_null_for_invalid_value(): void
    {
        $this->assertNull(PermissionType::tryFrom('invalid'));
        $this->assertNull(PermissionType::tryFrom(''));
        $this->assertNull(PermissionType::tryFrom('Resource'));
    }

    /** @test */
    public function it_all_cases_have_non_null_label_color_icon(): void
    {
        foreach (PermissionType::cases() as $case) {
            $this->assertNotNull($case->getLabel(), "Label for {$case->value} should not be null");
            $this->assertNotNull($case->getColor(), "Color for {$case->value} should not be null");
            $this->assertNotNull($case->getIcon(), "Icon for {$case->value} should not be null");
        }
    }
}
