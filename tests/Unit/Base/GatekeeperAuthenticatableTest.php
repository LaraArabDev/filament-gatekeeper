<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Base;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use LaraArabDev\FilamentGatekeeper\Base\GatekeeperAuthenticatable;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

class ConcreteAuthenticatable extends GatekeeperAuthenticatable
{
    use HasFactory;

    protected $table = 'users';
    protected $fillable = ['name', 'email', 'password'];
}

class GatekeeperAuthenticatableTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_has_web_as_default_guard_name(): void
    {
        $user = new ConcreteAuthenticatable();
        $this->assertSame('web', $user->getGuardName());
    }

    /** @test */
    public function it_can_set_and_get_guard_name(): void
    {
        $user = new ConcreteAuthenticatable();
        $result = $user->setGuardName('api');

        $this->assertSame('api', $user->getGuardName());
        $this->assertSame($user, $result);
    }

    /** @test */
    public function it_returns_false_for_is_super_admin_without_role(): void
    {
        $user = ConcreteAuthenticatable::create([
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'secret',
        ]);

        $this->assertFalse($user->isSuperAdmin());
    }

    /** @test */
    public function it_returns_true_for_is_super_admin_with_super_admin_role(): void
    {
        config()->set('gatekeeper.super_admin.role', 'super-admin');

        $user = ConcreteAuthenticatable::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => 'secret',
        ]);

        $user->assignRole(
            \LaraArabDev\FilamentGatekeeper\Models\Role::create(['name' => 'super-admin', 'guard_name' => 'web'])
        );

        $this->assertTrue($user->isSuperAdmin());
    }

    /** @test */
    public function it_has_has_roles_trait(): void
    {
        $user = new ConcreteAuthenticatable();
        $this->assertTrue(method_exists($user, 'assignRole'));
        $this->assertTrue(method_exists($user, 'hasRole'));
        $this->assertTrue(method_exists($user, 'hasPermissionTo'));
    }
}
