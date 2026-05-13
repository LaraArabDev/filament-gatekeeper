<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Base;

use Illuminate\Http\Resources\Json\JsonResource;
use LaraArabDev\FilamentGatekeeper\Concerns\HasResourcePermissions;

/**
 * Base Shield API Resource
 *
 * Extend this class for Laravel API Resources with built-in permission support.
 *
 * Usage:
 * ```php
 * use LaraArabDev\FilamentGatekeeper\Base\GatekeeperApiResource;
 *
 * class UserResource extends GatekeeperApiResource
 * {
 *     protected string $shieldModel = 'User';
 *
 *     public function toArray($request): array
 *     {
 *         return [
 *             'id' => $this->id,
 *             'name' => $this->name,
 *             'email' => $this->whenCanView('email', $this->email),
 *             'salary' => $this->whenCanView('salary', $this->salary),
 *             'posts' => $this->whenCanLoadRelation('posts', PostResource::class),
 *         ];
 *     }
 * }
 * ```
 */
class GatekeeperApiResource extends JsonResource
{
    use HasResourcePermissions;
}
