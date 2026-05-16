<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use LaraArabDev\FilamentGatekeeper\Models\Role;

class PermissionCache
{
    protected string $prefix;

    protected int $ttl;

    protected ?string $driver;

    /** @var array<string> */
    protected array $tags;

    public function __construct()
    {
        $this->prefix = config('gatekeeper.cache.prefix', 'shield');
        $this->ttl = config('gatekeeper.cache.ttl', 3600);
        $this->driver = config('gatekeeper.cache.driver');
        $this->tags = config('gatekeeper.cache.tags', ['gatekeeper']);
    }

    /**
     * Get the permission matrix for a user.
     *
     * @return array<string, mixed>
     */
    public function getPermissionMatrix(Authenticatable $user): array
    {
        if (! config('gatekeeper.cache.enabled', true)) {
            return $this->buildPermissionMatrix($user);
        }

        $cacheKey = $this->getCacheKey($user);

        return $this->cache()->remember(
            $cacheKey,
            $this->ttl,
            fn () => $this->buildPermissionMatrix($user)
        );
    }

    /**
     * Build the permission matrix for a user.
     *
     * @return array<string, mixed>
     */
    protected function buildPermissionMatrix(Authenticatable $user): array
    {
        $matrix = [];

        if (! method_exists($user, 'roles')) {
            return $matrix;
        }

        /** @var Collection<int, Role> $roles */
        $roles = $user->roles;

        foreach ($roles as $role) {
            if (! $role instanceof Role) {
                continue;
            }

            $fieldPermissions = $role->field_permissions ?? [];

            foreach ($fieldPermissions as $modelName => $permissions) {
                if (! isset($matrix[$modelName])) {
                    $matrix[$modelName] = [
                        'fields' => [],
                        'columns' => [],
                        'actions' => [],
                        'relations' => [],
                    ];
                }

                foreach ($permissions['fields'] ?? [] as $field => $fieldPerms) {
                    if (! isset($matrix[$modelName]['fields'][$field])) {
                        $matrix[$modelName]['fields'][$field] = ['view' => false, 'update' => false];
                    }
                    $matrix[$modelName]['fields'][$field]['view'] = $matrix[$modelName]['fields'][$field]['view'] || ($fieldPerms['view'] ?? false);
                    $matrix[$modelName]['fields'][$field]['update'] = $matrix[$modelName]['fields'][$field]['update'] || ($fieldPerms['update'] ?? false);
                }

                foreach ($permissions['columns'] ?? [] as $column => $canView) {
                    $matrix[$modelName]['columns'][$column] = ($matrix[$modelName]['columns'][$column] ?? false) || $canView;
                }

                foreach ($permissions['actions'] ?? [] as $action => $canExecute) {
                    $matrix[$modelName]['actions'][$action] = ($matrix[$modelName]['actions'][$action] ?? false) || $canExecute;
                }

                foreach ($permissions['relations'] ?? [] as $relation => $relationPerms) {
                    if (! isset($matrix[$modelName]['relations'][$relation])) {
                        $matrix[$modelName]['relations'][$relation] = [];
                    }
                    $matrix[$modelName]['relations'][$relation] = array_unique(
                        array_merge($matrix[$modelName]['relations'][$relation], $relationPerms)
                    );
                }
            }
        }

        return $matrix;
    }

    /**
     * Invalidate cache for a specific user.
     */
    public function invalidateUser(Authenticatable $user): void
    {
        $cacheKey = $this->getCacheKey($user);
        $this->cache()->forget($cacheKey);
    }

    /**
     * Alias for invalidateUser (for consistency with test naming).
     */
    public function invalidateForUser(Authenticatable $user): void
    {
        $this->invalidateUser($user);
    }

    /**
     * Get the cache key for user permissions.
     */
    public function getUserPermissionsCacheKey(Authenticatable $user): string
    {
        return $this->getCacheKey($user);
    }

    /**
     * Invalidate cache for all users with a specific role.
     */
    public function invalidateRole(Role $role): void
    {
        $this->cache()->forget("{$this->prefix}:role:{$role->id}");

        $this->invalidateAll();
    }

    /**
     * Invalidate all Gatekeeper cache.
     */
    public function invalidateAll(): void
    {
        try {
            if ($this->supportsTagging()) {
                Cache::tags($this->tags)->flush();
            }
        } catch (\Exception $e) {
        }
    }

    /**
     * Get the cache key for a user.
     */
    protected function getCacheKey(Authenticatable $user): string
    {
        $userId = $user->getAuthIdentifier();

        return "{$this->prefix}:user:{$userId}:matrix";
    }

    /**
     * Get the cache instance.
     */
    protected function cache(): Repository
    {
        $store = $this->driver ? Cache::store($this->driver) : Cache::store();

        if ($this->supportsTagging()) {
            return $store->tags($this->tags);
        }

        return $store;
    }

    /**
     * Check if the cache driver supports tagging.
     */
    protected function supportsTagging(): bool
    {
        $driver = $this->driver ?? config('cache.default');

        return in_array($driver, ['redis', 'memcached', 'dynamodb', 'array']);
    }

    /**
     * Warm the cache for a user.
     */
    public function warmCache(Authenticatable $user): void
    {
        $this->getPermissionMatrix($user);
    }

    /**
     * Remember a value in cache.
     */
    public function remember(string $key, callable $callback): mixed
    {
        if (! config('gatekeeper.cache.enabled', true)) {
            return $callback();
        }

        $fullKey = "{$this->prefix}:{$key}";

        return $this->cache()->remember($fullKey, $this->ttl, $callback);
    }

    /**
     * Forget a cached value.
     */
    public function forget(string $key): bool
    {
        $fullKey = "{$this->prefix}:{$key}";

        return $this->cache()->forget($fullKey);
    }

    /**
     * Flush all Gatekeeper cache.
     */
    public function flushAll(): void
    {
        $this->invalidateAll();
    }

    /**
     * Get cache statistics.
     *
     * @return array<string, mixed>
     */
    public function getStats(): array
    {
        return [
            'prefix' => $this->prefix,
            'ttl' => $this->ttl,
            'driver' => $this->driver ?? config('cache.default'),
            'tags' => $this->tags,
            'supports_tagging' => $this->supportsTagging(),
        ];
    }
}
