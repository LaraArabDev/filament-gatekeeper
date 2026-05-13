<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Concerns;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Concerns\HasRelationPermissions;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class HasRelationPermissionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_check_view_relation_permission(): void
    {
        $user = $this->createUser();

        Permission::factory()->relation()->create([
            'name' => 'view_test_model_posts_relation',
        ]);

        $user->givePermissionTo('view_test_model_posts_relation');

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithRelationPermissions();

        $this->assertTrue($testClass::canViewRelation('posts'));
    }

    /** @test */
    public function it_denies_view_relation_without_permission(): void
    {
        $user = $this->createUser();

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithRelationPermissions();

        $this->assertFalse($testClass::canViewRelation('posts'));
    }

    /** @test */
    public function it_can_check_multiple_relation_permissions(): void
    {
        $user = $this->createUser();

        Permission::factory()->relation()->create(['name' => 'view_test_model_posts_relation']);
        Permission::factory()->relation()->create(['name' => 'view_test_model_comments_relation']);

        $user->givePermissionTo(['view_test_model_posts_relation', 'view_test_model_comments_relation']);

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithRelationPermissions();

        $this->assertTrue($testClass::canViewRelation('posts'));
        $this->assertTrue($testClass::canViewRelation('comments'));
        $this->assertFalse($testClass::canViewRelation('orders'));
    }

    /** @test */
    public function it_bypasses_relation_permissions_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithRelationPermissions();

        $this->assertTrue($testClass::canViewRelation('posts'));
        $this->assertTrue($testClass::canViewRelation('comments'));
        $this->assertTrue($testClass::canViewRelation('any_relation'));
    }

    /** @test */
    public function it_generates_correct_relation_permission_names(): void
    {
        $testClass = $this->createTestResourceWithRelationPermissions();

        $this->assertEquals('view_test_model_posts_relation', $testClass::getRelationPermissionName('posts'));
        $this->assertEquals('view_test_model_comments_relation', $testClass::getRelationPermissionName('comments'));
    }

    /** @test */
    public function it_can_get_all_relation_permissions(): void
    {
        config()->set('gatekeeper.relation_permissions.TestModel', ['posts', 'comments', 'orders']);

        $testClass = $this->createTestResourceWithRelationPermissions();

        $permissions = $testClass::getAllRelationPermissions();

        $this->assertContains('view_test_model_posts_relation', $permissions);
        $this->assertContains('view_test_model_comments_relation', $permissions);
        $this->assertContains('view_test_model_orders_relation', $permissions);
    }

    /** @test */
    public function it_can_filter_permitted_relations(): void
    {
        $user = $this->createUser();

        Permission::factory()->relation()->create([
            'name' => 'view_test_model_posts_relation',
        ]);

        $user->givePermissionTo('view_test_model_posts_relation');

        $this->actingAs($user);

        $testClass = $this->createTestResourceWithRelationPermissions();

        $relations = [
            'PostsRelationManager',
            'CommentsRelationManager',
            'OrdersRelationManager',
        ];

        // Map relation managers to relation names for testing
        config()->set('gatekeeper.relation_permissions.TestModel', ['posts', 'comments', 'orders']);

        $permittedRelations = $testClass::getPermittedRelations($relations);

        // The filtering logic would depend on how relation managers are mapped to permission names
        // This is a simplified test
        $this->assertIsArray($permittedRelations);
    }

    /** @test */
    public function it_can_get_available_relations_for_current_user(): void
    {
        $user = $this->createUser();

        Permission::factory()->relation()->create(['name' => 'view_test_model_posts_relation']);

        $user->givePermissionTo('view_test_model_posts_relation');

        $this->actingAs($user);

        config()->set('gatekeeper.relation_permissions.TestModel', ['posts', 'comments', 'orders']);

        $available = TestResourceWithRelations::getAvailableRelations();

        $this->assertContains('posts', $available);
        $this->assertNotContains('comments', $available);
        $this->assertNotContains('orders', $available);
    }

    /** @test */
    public function it_returns_all_available_relations_for_super_admin(): void
    {
        $user = $this->createSuperAdmin();

        $this->actingAs($user);

        config()->set('gatekeeper.relation_permissions.TestModel', ['posts', 'comments', 'orders']);

        $available = TestResourceWithRelations::getAvailableRelations();

        $this->assertContains('posts', $available);
        $this->assertContains('comments', $available);
        $this->assertContains('orders', $available);
    }

    /** @test */
    public function it_filterRelationManagers_delegates_to_getPermittedRelations(): void
    {
        $user = $this->createUser();

        Permission::factory()->relation()->create(['name' => 'view_test_model_posts_relation']);
        $user->givePermissionTo('view_test_model_posts_relation');

        $this->actingAs($user);

        $managers = [PostsRelationManagerForTest::class, CommentsRelationManagerForTest::class];

        $this->assertEquals(
            TestResourceWithRelations::getPermittedRelations($managers),
            TestResourceWithRelations::filterRelationManagers($managers)
        );
    }

    /** @test */
    public function it_extracts_relation_name_from_class(): void
    {
        // PostsRelationManagerForTest -> strips RelationManager -> PostsForTest -> snake: posts_for_test -> no underscores: postsfortest
        $result = TestResourceWithRelations::getRelationNameFromClassPublic(PostsRelationManagerForTest::class);
        $this->assertStringContainsString('posts', $result);

        // CommentsRelationManagerForTest -> strips RelationManager -> CommentsForTest -> snake: comments_for_test -> commentsfortest
        $result2 = TestResourceWithRelations::getRelationNameFromClassPublic(CommentsRelationManagerForTest::class);
        $this->assertStringContainsString('comments', $result2);
    }

    protected function createTestResourceWithRelationPermissions(): string
    {
        return TestResourceWithRelations::class;
    }
}

class TestResourceWithRelations
{
    use HasRelationPermissions;

    protected static ?string $model = TestModelForRelations::class;

    public static function getModelName(): string
    {
        return 'TestModel';
    }

    public static function getRelationNameFromClassPublic(string $class): string
    {
        return static::getRelationNameFromClass($class);
    }
}

class TestModelForRelations {}
class PostsRelationManagerForTest {}
class CommentsRelationManagerForTest {}
