<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use LaraArabDev\FilamentGatekeeper\Tests\TestUser;

/**
 * @extends Factory<TestUser>
 */
class UserFactory extends Factory
{
    protected $model = TestUser::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => bcrypt('password'),
        ];
    }
}

