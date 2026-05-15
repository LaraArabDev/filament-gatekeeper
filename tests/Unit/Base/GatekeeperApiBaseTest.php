<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Base;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use LaraArabDev\FilamentGatekeeper\Base\GatekeeperApiCollection;
use LaraArabDev\FilamentGatekeeper\Base\GatekeeperApiResource;
use LaraArabDev\FilamentGatekeeper\Concerns\HasResourcePermissions;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

// ------------------------------------------------------------------
// Concrete subclasses for testing abstract base behaviour
// ------------------------------------------------------------------

class ConcreteApiResource extends GatekeeperApiResource
{
    protected string $shieldModel = 'User';
    protected string $shieldGuard = 'web';

    public function toArray(Request $request): array
    {
        return [
            'email' => $this->whenCanView('email', $this->resource['email'] ?? null),
        ];
    }

    // Expose protected methods for testing
    public function callGetGatekeeperModel(): string
    {
        return $this->getGatekeeperModel();
    }

    public function callGetShieldGuard(): string
    {
        return $this->getShieldGuard();
    }

    public function callCanViewField(string $field): bool
    {
        return $this->canViewField($field);
    }

    public function callCanViewColumn(string $column): bool
    {
        return $this->canViewColumn($column);
    }

    public function callCanViewRelation(string $relation): bool
    {
        return $this->canViewRelation($relation);
    }
}

class ConcreteApiResourceNoModel extends GatekeeperApiResource
{
    // No shieldModel property → should derive from class name
    public function toArray(Request $request): array
    {
        return [];
    }

    public function callGetGatekeeperModel(): string
    {
        return $this->getGatekeeperModel();
    }
}

class ConcreteApiCollection extends GatekeeperApiCollection
{
    protected string $shieldModel = 'User';
    protected string $shieldGuard = 'web';

    public $collects = ConcreteApiResource::class;

    public function callGetGatekeeperModel(): string
    {
        return $this->getGatekeeperModel();
    }

    public function callGetShieldGuard(): string
    {
        return $this->getShieldGuard();
    }
}

// ------------------------------------------------------------------
// Tests for GatekeeperApiResource
// ------------------------------------------------------------------

class GatekeeperApiResourceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_extends_json_resource(): void
    {
        $resource = new ConcreteApiResource(['email' => 'test@example.com']);
        $this->assertInstanceOf(JsonResource::class, $resource);
    }

    /** @test */
    public function it_uses_has_resource_permissions_trait(): void
    {
        $traits = class_uses_recursive(GatekeeperApiResource::class);
        $this->assertContains(HasResourcePermissions::class, $traits);
    }

    /** @test */
    public function it_returns_shield_model_from_property(): void
    {
        $resource = new ConcreteApiResource(['email' => 'test@example.com']);
        $this->assertSame('User', $resource->callGetGatekeeperModel());
    }

    /** @test */
    public function it_derives_model_name_from_class_name_when_no_shield_model(): void
    {
        $resource = new ConcreteApiResourceNoModel(['email' => 'test@example.com']);
        $modelName = $resource->callGetGatekeeperModel();
        // Class is "ConcreteApiResourceNoModel" → strips "Resource" and "Collection"
        $this->assertIsString($modelName);
        $this->assertNotEmpty($modelName);
    }

    /** @test */
    public function it_returns_shield_guard_from_property(): void
    {
        $resource = new ConcreteApiResource(['email' => 'test@example.com']);
        $this->assertSame('web', $resource->callGetShieldGuard());
    }

    /** @test */
    public function it_can_check_view_field_permission(): void
    {
        $user = $this->createUser();
        Permission::factory()->field()->create(['name' => 'view_user_email_field']);
        $user->givePermissionTo('view_user_email_field');
        $this->actingAs($user);

        $resource = new ConcreteApiResource(['email' => 'test@example.com']);
        $this->assertTrue($resource->callCanViewField('email'));
    }

    /** @test */
    public function it_denies_view_field_without_permission(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $resource = new ConcreteApiResource(['email' => 'test@example.com']);
        $this->assertFalse($resource->callCanViewField('salary'));
    }

    /** @test */
    public function it_can_check_view_column_permission(): void
    {
        $user = $this->createUser();
        Permission::factory()->column()->create(['name' => 'view_user_email_column']);
        $user->givePermissionTo('view_user_email_column');
        $this->actingAs($user);

        $resource = new ConcreteApiResource(['email' => 'test@example.com']);
        $this->assertTrue($resource->callCanViewColumn('email'));
    }

    /** @test */
    public function it_can_check_view_relation_permission(): void
    {
        $user = $this->createUser();
        Permission::factory()->relation()->create(['name' => 'view_user_posts_relation']);
        $user->givePermissionTo('view_user_posts_relation');
        $this->actingAs($user);

        $resource = new ConcreteApiResource([]);
        $this->assertTrue($resource->callCanViewRelation('posts'));
    }

    /** @test */
    public function it_denies_view_relation_without_permission(): void
    {
        $user = $this->createUser();
        $this->actingAs($user);

        $resource = new ConcreteApiResource([]);
        $this->assertFalse($resource->callCanViewRelation('orders'));
    }

    /** @test */
    public function it_bypasses_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();
        $this->actingAs($user);

        $resource = new ConcreteApiResource(['email' => 'test@example.com']);
        $this->assertTrue($resource->callCanViewField('email'));
        $this->assertTrue($resource->callCanViewColumn('salary'));
        $this->assertTrue($resource->callCanViewRelation('orders'));
    }
}

// ------------------------------------------------------------------
// Tests for GatekeeperApiCollection
// ------------------------------------------------------------------

class GatekeeperApiCollectionTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_extends_resource_collection(): void
    {
        $collection = new ConcreteApiCollection(collect([]));
        $this->assertInstanceOf(ResourceCollection::class, $collection);
    }

    /** @test */
    public function it_uses_has_resource_permissions_trait(): void
    {
        $traits = class_uses_recursive(GatekeeperApiCollection::class);
        $this->assertContains(HasResourcePermissions::class, $traits);
    }

    /** @test */
    public function it_returns_shield_model_from_property(): void
    {
        $collection = new ConcreteApiCollection(collect([]));
        $this->assertSame('User', $collection->callGetGatekeeperModel());
    }

    /** @test */
    public function it_returns_shield_guard_from_property(): void
    {
        $collection = new ConcreteApiCollection(collect([]));
        $this->assertSame('web', $collection->callGetShieldGuard());
    }

    /** @test */
    public function it_can_be_instantiated_with_empty_collection(): void
    {
        $collection = new ConcreteApiCollection(collect([]));
        $this->assertInstanceOf(ConcreteApiCollection::class, $collection);
    }

    /** @test */
    public function it_can_be_instantiated_with_collection_of_items(): void
    {
        $items = collect([
            ['id' => 1, 'email' => 'a@example.com'],
            ['id' => 2, 'email' => 'b@example.com'],
        ]);
        $collection = new ConcreteApiCollection($items);
        $this->assertInstanceOf(ConcreteApiCollection::class, $collection);
    }
}
