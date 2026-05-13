<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Contracts;

/**
 * Interface for permission registration and synchronization services.
 *
 * This interface defines the contract for services that handle permission
 * discovery, creation, and synchronization within the Shield Manager package.
 *
 * @package LaraArabDev\FilamentGatekeeper\Contracts
 */
interface PermissionRegistrarInterface
{
    /**
     * Synchronize all permissions.
     *
     * Discovers and creates permissions for all entity types including
     * models, resources, pages, widgets, fields, columns, actions, and relations.
     *
     * @return array<string, array<string>> Sync operation log
     */
    public function syncAll(): array;

    /**
     * Set dry run mode.
     *
     * When enabled, no actual database changes are made.
     *
     * @param bool $dryRun Whether to enable dry run mode
     * @return static Returns self for method chaining
     */
    public function dryRun(bool $dryRun = true): static;
}
