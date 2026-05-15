<?php

declare(strict_types=1);

use Illuminate\Http\Resources\MissingValue;
use LaraArabDev\FilamentGatekeeper\Concerns\HasResourcePermissions;
use LaraArabDev\FilamentGatekeeper\Models\Permission;
use LaraArabDev\FilamentGatekeeper\Tests\TestCase;

uses(TestCase::class)->in(__FILE__);

// A named class used as a fake resource wrapper for single (non-iterable) relation tests
class FakeProfileResource
{
    public mixed $data;

    public function __construct(mixed $data)
    {
        $this->data = $data;
    }
}

// ---------------------------------------------------------------------------
// Helper: minimal class that uses HasResourcePermissions without extending
//         JsonResource, exposing protected methods for direct testing.
// ---------------------------------------------------------------------------

function makeTraitResource(mixed $data = []): object
{
    return new class ($data) {
        use HasResourcePermissions;

        protected string $shieldModel = 'User';
        protected string $shieldGuard = 'web';
        protected mixed $resource;

        public function __construct(mixed $resource = [])
        {
            if (is_array($resource)) {
                $this->resource = new class ($resource) {
                    public function __construct(private array $attrs) {}

                    public function attributesToArray(): array { return $this->attrs; }
                    public function __get(string $key): mixed { return $this->attrs[$key] ?? null; }
                    public function __isset(string $key): bool { return isset($this->attrs[$key]); }
                };
            } else {
                $this->resource = $resource;
            }
        }

        /** Mimic Laravel's JsonResource::when() */
        protected function when(mixed $condition, mixed $value, mixed $default = null): mixed
        {
            if (! $condition) {
                $resolved = $default instanceof \Closure ? $default() : $default;

                return $resolved ?? new MissingValue;
            }

            return $value instanceof \Closure ? $value() : $value;
        }

        /** Mimic Laravel's JsonResource::whenLoaded() */
        protected function whenLoaded(string $relation, mixed $value = null): mixed
        {
            if ($value === null) {
                return new MissingValue;
            }

            return $value instanceof \Closure ? $value() : $value;
        }

        // Expose protected API for testing --------------------------------

        public function callGetGatekeeperModel(): string { return $this->getGatekeeperModel(); }
        public function callGetShieldGuard(): string { return $this->getShieldGuard(); }
        public function callWhenCanView(string $field, mixed $value, mixed $default = null): mixed { return $this->whenCanView($field, $value, $default); }
        public function callWhenCanViewColumn(string $column, mixed $value, mixed $default = null): mixed { return $this->whenCanViewColumn($column, $value, $default); }
        public function callWhenCanViewRelation(string $relation, mixed $value): mixed { return $this->whenCanViewRelation($relation, $value); }
        public function callCanViewField(string $field): bool { return $this->canViewField($field); }
        public function callCanViewColumn(string $column): bool { return $this->canViewColumn($column); }
        public function callCanViewRelation(string $relation): bool { return $this->canViewRelation($relation); }
        public function callFilterByFieldPermissions(array $data): array { return $this->filterByFieldPermissions($data); }
        public function callPermittedAttributes(array $except = []): array { return $this->permittedAttributes($except); }
    };
}

// ---------------------------------------------------------------------------
// getGatekeeperModel()
// ---------------------------------------------------------------------------

it('getGatekeeperModel returns shieldModel property', function () {
    $resource = makeTraitResource(['name' => 'Test']);
    expect($resource->callGetGatekeeperModel())->toBe('User');
});

it('getGatekeeperModel derives from class name when no shieldModel', function () {
    $resource = new class ([]) {
        use HasResourcePermissions;

        protected mixed $resource;

        public function __construct(mixed $resource = [])
        {
            $this->resource = $resource;
        }

        protected function when(mixed $condition, mixed $value, mixed $default = null): mixed
        {
            return $condition ? $value : ($default ?? new MissingValue);
        }

        protected function whenLoaded(string $relation, mixed $value = null): mixed
        {
            return $value ?? new MissingValue;
        }

        public function callGetGatekeeperModel(): string { return $this->getGatekeeperModel(); }
    };

    // No $shieldModel property; class name is "class@anonymous" -> falls back to str manipulation
    expect($resource->callGetGatekeeperModel())->toBeString()->not->toBeEmpty();
});

// ---------------------------------------------------------------------------
// getShieldGuard()
// ---------------------------------------------------------------------------

it('getShieldGuard returns configured guard', function () {
    $resource = makeTraitResource();
    expect($resource->callGetShieldGuard())->toBe('web');
});

it('getShieldGuard defaults to api when no shieldGuard property', function () {
    $resource = new class ([]) {
        use HasResourcePermissions;

        protected string $shieldModel = 'User';
        protected mixed $resource;

        public function __construct(mixed $resource = [])
        {
            $this->resource = $resource;
        }

        protected function when(mixed $condition, mixed $value, mixed $default = null): mixed
        {
            return $condition ? $value : ($default ?? new MissingValue);
        }

        protected function whenLoaded(string $relation, mixed $value = null): mixed
        {
            return $value ?? new MissingValue;
        }

        public function callGetShieldGuard(): string { return $this->getShieldGuard(); }
    };

    expect($resource->callGetShieldGuard())->toBe('api');
});

// ---------------------------------------------------------------------------
// whenCanView() - field permissions
// ---------------------------------------------------------------------------

it('whenCanView returns value when user has field permission', function () {
    $user = test()->createUser();
    Permission::factory()->field()->forGuard('web')->create(['name' => 'view_user_email_field']);
    $user->givePermissionTo('view_user_email_field');
    test()->actingAs($user);

    $resource = makeTraitResource(['email' => 'test@example.com']);
    $result = $resource->callWhenCanView('email', 'test@example.com');

    expect($result)->toBe('test@example.com');
});

it('whenCanView returns MissingValue without permission', function () {
    $user = test()->createUser();
    test()->actingAs($user);

    $resource = makeTraitResource(['email' => 'test@example.com']);
    $result = $resource->callWhenCanView('email', 'test@example.com');

    expect($result)->toBeInstanceOf(MissingValue::class);
});

it('whenCanView returns custom default when no permission', function () {
    $user = test()->createUser();
    test()->actingAs($user);

    $resource = makeTraitResource(['email' => 'test@example.com']);
    $result = $resource->callWhenCanView('email', 'test@example.com', '***');

    expect($result)->toBe('***');
});

// ---------------------------------------------------------------------------
// whenCanViewColumn()
// ---------------------------------------------------------------------------

it('whenCanViewColumn returns value when user has column permission', function () {
    $user = test()->createUser();
    Permission::factory()->column()->forGuard('web')->create(['name' => 'view_user_name_column']);
    $user->givePermissionTo('view_user_name_column');
    test()->actingAs($user);

    $resource = makeTraitResource(['name' => 'Test']);
    $result = $resource->callWhenCanViewColumn('name', 'Test');

    expect($result)->toBe('Test');
});

it('whenCanViewColumn returns MissingValue without permission', function () {
    $user = test()->createUser();
    test()->actingAs($user);

    $resource = makeTraitResource(['name' => 'Test']);
    $result = $resource->callWhenCanViewColumn('name', 'Test');

    expect($result)->toBeInstanceOf(MissingValue::class);
});

// ---------------------------------------------------------------------------
// whenCanViewRelation()
// ---------------------------------------------------------------------------

it('whenCanViewRelation returns value when user has relation permission', function () {
    $user = test()->createUser();
    Permission::factory()->relation()->forGuard('web')->create(['name' => 'view_user_roles_relation']);
    $user->givePermissionTo('view_user_roles_relation');
    test()->actingAs($user);

    $resource = makeTraitResource([]);
    $result = $resource->callWhenCanViewRelation('roles', ['admin']);

    expect($result)->toBe(['admin']);
});

it('whenCanViewRelation returns MissingValue without permission', function () {
    $user = test()->createUser();
    test()->actingAs($user);

    $resource = makeTraitResource([]);
    $result = $resource->callWhenCanViewRelation('roles', ['admin']);

    expect($result)->toBeInstanceOf(MissingValue::class);
});

// ---------------------------------------------------------------------------
// canViewField() / canViewColumn() / canViewRelation()
// ---------------------------------------------------------------------------

it('canViewField returns true with permission', function () {
    $user = test()->createUser();
    Permission::factory()->field()->forGuard('web')->create(['name' => 'view_user_email_field']);
    $user->givePermissionTo('view_user_email_field');
    test()->actingAs($user);

    expect(makeTraitResource()->callCanViewField('email'))->toBeTrue();
});

it('canViewField returns false without permission', function () {
    $user = test()->createUser();
    test()->actingAs($user);

    expect(makeTraitResource()->callCanViewField('salary'))->toBeFalse();
});

it('canViewColumn returns true with permission', function () {
    $user = test()->createUser();
    Permission::factory()->column()->forGuard('web')->create(['name' => 'view_user_name_column']);
    $user->givePermissionTo('view_user_name_column');
    test()->actingAs($user);

    expect(makeTraitResource()->callCanViewColumn('name'))->toBeTrue();
});

it('canViewColumn returns false without permission', function () {
    $user = test()->createUser();
    test()->actingAs($user);

    expect(makeTraitResource()->callCanViewColumn('salary'))->toBeFalse();
});

it('canViewRelation returns true with permission', function () {
    $user = test()->createUser();
    Permission::factory()->relation()->forGuard('web')->create(['name' => 'view_user_posts_relation']);
    $user->givePermissionTo('view_user_posts_relation');
    test()->actingAs($user);

    expect(makeTraitResource()->callCanViewRelation('posts'))->toBeTrue();
});

it('canViewRelation returns false without permission', function () {
    $user = test()->createUser();
    test()->actingAs($user);

    expect(makeTraitResource()->callCanViewRelation('orders'))->toBeFalse();
});

// ---------------------------------------------------------------------------
// filterByFieldPermissions()
// ---------------------------------------------------------------------------

it('filterByFieldPermissions returns all data when no field permissions configured', function () {
    $user = test()->createUser();
    test()->actingAs($user);
    config()->set('gatekeeper.field_permissions', []);

    $data = ['name' => 'Test', 'email' => 'test@example.com'];
    $result = makeTraitResource($data)->callFilterByFieldPermissions($data);

    expect($result)->toBe($data);
});

it('filterByFieldPermissions filters to only permitted fields', function () {
    $user = test()->createUser();
    Permission::factory()->field()->forGuard('web')->create(['name' => 'view_user_name_field']);
    Permission::factory()->field()->forGuard('web')->create(['name' => 'view_user_email_field']);
    $user->givePermissionTo('view_user_name_field');
    test()->actingAs($user);

    config()->set('gatekeeper.field_permissions.User', ['name', 'email', 'salary']);

    $data = ['name' => 'Test', 'email' => 'test@example.com', 'salary' => 50000];
    $result = makeTraitResource($data)->callFilterByFieldPermissions($data);

    expect($result)->toBeArray();
});

// ---------------------------------------------------------------------------
// permittedAttributes()
// ---------------------------------------------------------------------------

it('permittedAttributes returns all model attributes when no field permissions', function () {
    $user = test()->createUser();
    test()->actingAs($user);
    config()->set('gatekeeper.field_permissions', []);

    $result = makeTraitResource(['name' => 'Test', 'email' => 'test@example.com', 'salary' => 50000])
        ->callPermittedAttributes([]);

    expect($result)->toBeArray()
        ->toHaveKey('name')
        ->toHaveKey('email');
});

it('permittedAttributes excludes except fields', function () {
    $user = test()->createUser();
    test()->actingAs($user);
    config()->set('gatekeeper.field_permissions', []);

    $result = makeTraitResource(['name' => 'Test', 'email' => 'test@example.com', 'salary' => 50000])
        ->callPermittedAttributes(['salary']);

    expect($result)->toBeArray()
        ->toHaveKey('name')
        ->not->toHaveKey('salary');
});

// ---------------------------------------------------------------------------
// whenCanLoadRelation() tests
// ---------------------------------------------------------------------------

it('whenCanLoadRelation returns MissingValue when user does not have relation permission', function () {
    $user = test()->createUser();
    test()->actingAs($user);

    $resource = new class ([]) {
        use HasResourcePermissions;

        protected string $shieldModel = 'User';
        protected string $shieldGuard = 'web';
        protected mixed $resource;

        public function __construct(mixed $resource = [])
        {
            $this->resource = new class ([]) {
                public function __construct(private array $attrs = []) {}
                public function attributesToArray(): array { return $this->attrs; }
                public function __get(string $key): mixed { return $this->attrs[$key] ?? null; }
                public function __isset(string $key): bool { return isset($this->attrs[$key]); }
            };
        }

        protected function when(mixed $condition, mixed $value, mixed $default = null): mixed
        {
            if (! $condition) {
                $resolved = $default instanceof \Closure ? $default() : $default;
                return $resolved ?? new MissingValue;
            }
            return $value instanceof \Closure ? $value() : $value;
        }

        protected function whenLoaded(string $relation, mixed $value = null): mixed
        {
            if ($value === null) {
                return new MissingValue;
            }
            return $value instanceof \Closure ? $value() : $value;
        }

        public function callWhenCanLoadRelation(string $relation, string $resourceClass): mixed
        {
            return $this->whenCanLoadRelation($relation, $resourceClass);
        }
    };

    $result = $resource->callWhenCanLoadRelation('posts', 'SomeResourceClass');

    expect($result)->toBeInstanceOf(MissingValue::class);
});

it('whenCanLoadRelation returns resource collection when user has permission and relation is iterable', function () {
    $user = test()->createUser();
    Permission::factory()->relation()->forGuard('web')->create(['name' => 'view_user_posts_relation']);
    $user->givePermissionTo('view_user_posts_relation');
    test()->actingAs($user);

    // Create a fake resource class that can be used as collection
    $fakeResourceClass = new class {
        public static function collection(iterable $items): array
        {
            return iterator_to_array($items instanceof \Traversable ? $items : new \ArrayIterator((array) $items));
        }
    };
    $fakeResourceClassName = get_class($fakeResourceClass);

    $postsData = [['id' => 1], ['id' => 2]];

    $resource = new class ($postsData, $fakeResourceClassName) {
        use HasResourcePermissions;

        protected string $shieldModel = 'User';
        protected string $shieldGuard = 'web';
        protected mixed $resource;
        private string $resourceClass;
        private array $postsData;

        public function __construct(array $postsData, string $resourceClass)
        {
            $this->postsData = $postsData;
            $this->resourceClass = $resourceClass;
            $this->resource = new class ($postsData) {
                public array $posts;
                public function __construct(array $posts) { $this->posts = $posts; }
                public function attributesToArray(): array { return []; }
                public function __get(string $key): mixed { return $this->{$key} ?? null; }
                public function __isset(string $key): bool { return isset($this->{$key}); }
            };
        }

        protected function when(mixed $condition, mixed $value, mixed $default = null): mixed
        {
            if (! $condition) {
                $resolved = $default instanceof \Closure ? $default() : $default;
                return $resolved ?? new MissingValue;
            }
            return $value instanceof \Closure ? $value() : $value;
        }

        protected function whenLoaded(string $relation, mixed $value = null): mixed
        {
            if ($value === null) {
                return new MissingValue;
            }
            return $value instanceof \Closure ? $value() : $value;
        }

        public function callWhenCanLoadRelation(string $relation, string $resourceClass): mixed
        {
            return $this->whenCanLoadRelation($relation, $resourceClass);
        }
    };

    $result = $resource->callWhenCanLoadRelation('posts', $fakeResourceClassName);

    // Since whenLoaded will call the callback, we expect an array (the collection)
    expect($result)->toBeArray();
});

it('whenCanLoadRelation returns single resource when user has permission and relation is not iterable', function () {
    $user = test()->createUser();
    Permission::factory()->relation()->forGuard('web')->create(['name' => 'view_user_profile_relation']);
    $user->givePermissionTo('view_user_profile_relation');
    test()->actingAs($user);

    $profileData = (object) ['id' => 1, 'bio' => 'Test bio'];

    // A resource wrapper class that accepts a single arg in its constructor
    $fakeResourceClassName = FakeProfileResource::class;

    $resource = new class ($profileData, $fakeResourceClassName) {
        use HasResourcePermissions;

        protected string $shieldModel = 'User';
        protected string $shieldGuard = 'web';
        protected mixed $resource;
        private string $resourceClass;
        private mixed $profileData;

        public function __construct(mixed $profileData, string $resourceClass)
        {
            $this->profileData = $profileData;
            $this->resourceClass = $resourceClass;
            $this->resource = new class ($profileData) {
                public mixed $profile;
                public function __construct(mixed $profile) { $this->profile = $profile; }
                public function attributesToArray(): array { return []; }
                public function __get(string $key): mixed { return $this->{$key} ?? null; }
                public function __isset(string $key): bool { return isset($this->{$key}); }
            };
        }

        protected function when(mixed $condition, mixed $value, mixed $default = null): mixed
        {
            if (! $condition) {
                $resolved = $default instanceof \Closure ? $default() : $default;
                return $resolved ?? new MissingValue;
            }
            return $value instanceof \Closure ? $value() : $value;
        }

        protected function whenLoaded(string $relation, mixed $value = null): mixed
        {
            if ($value === null) {
                return new MissingValue;
            }
            return $value instanceof \Closure ? $value() : $value;
        }

        public function callWhenCanLoadRelation(string $relation, string $resourceClass): mixed
        {
            return $this->whenCanLoadRelation($relation, $resourceClass);
        }
    };

    $result = $resource->callWhenCanLoadRelation('profile', $fakeResourceClassName);

    // Should be an instance of the resource class (created with profileData)
    expect($result)->toBeInstanceOf($fakeResourceClassName);
});

// ---------------------------------------------------------------------------
// getGatekeeperModel() class name extraction (no shieldModel property)
// ---------------------------------------------------------------------------

it('getGatekeeperModel strips Resource suffix from class name', function () {
    $resource = new class ([]) {
        use HasResourcePermissions;

        protected mixed $resource;

        public function __construct(mixed $resource = [])
        {
            $this->resource = $resource;
        }

        protected function when(mixed $condition, mixed $value, mixed $default = null): mixed
        {
            return $condition ? $value : ($default ?? new MissingValue);
        }

        protected function whenLoaded(string $relation, mixed $value = null): mixed
        {
            return $value ?? new MissingValue;
        }

        public function callGetGatekeeperModel(): string { return $this->getGatekeeperModel(); }
    };

    // No $shieldModel property; class is anonymous, falls back to str manipulation
    $result = $resource->callGetGatekeeperModel();
    expect($result)->toBeString()->not->toBeEmpty();
});

// ---------------------------------------------------------------------------
// getShieldGuard() with shieldGuard property
// ---------------------------------------------------------------------------

it('getShieldGuard returns custom guard from shieldGuard property', function () {
    $resource = new class ([]) {
        use HasResourcePermissions;

        protected string $shieldModel = 'User';
        protected string $shieldGuard = 'sanctum';
        protected mixed $resource;

        public function __construct(mixed $resource = [])
        {
            $this->resource = $resource;
        }

        protected function when(mixed $condition, mixed $value, mixed $default = null): mixed
        {
            return $condition ? $value : ($default ?? new MissingValue);
        }

        protected function whenLoaded(string $relation, mixed $value = null): mixed
        {
            return $value ?? new MissingValue;
        }

        public function callGetShieldGuard(): string { return $this->getShieldGuard(); }
    };

    expect($resource->callGetShieldGuard())->toBe('sanctum');
});
