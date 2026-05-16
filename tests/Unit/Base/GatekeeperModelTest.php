<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Tests\Unit\Base;

use Illuminate\Database\Eloquent\Model;
use LaraArabDev\FilamentGatekeeper\Base\GatekeeperModel;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ConcreteModel extends GatekeeperModel
{
    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password'];
}

class GatekeeperModelTest extends TestCase
{
    #[Test]
    public function it_has_web_as_default_guard_name(): void
    {
        $model = new ConcreteModel;
        $this->assertSame('web', $model->getGuardName());
    }

    #[Test]
    public function it_can_set_and_get_guard_name(): void
    {
        $model = new ConcreteModel;
        $result = $model->setGuardName('api');

        $this->assertSame('api', $model->getGuardName());
        $this->assertSame($model, $result);
    }

    #[Test]
    public function it_has_has_roles_trait(): void
    {
        $model = new ConcreteModel;
        $this->assertTrue(method_exists($model, 'assignRole'));
        $this->assertTrue(method_exists($model, 'hasRole'));
        $this->assertTrue(method_exists($model, 'hasPermissionTo'));
    }

    #[Test]
    public function it_is_an_eloquent_model(): void
    {
        $model = new ConcreteModel;
        $this->assertInstanceOf(Model::class, $model);
    }
}
