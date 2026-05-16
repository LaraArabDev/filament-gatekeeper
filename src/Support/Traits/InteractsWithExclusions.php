<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Support\Traits;

/**
 * Provides exclusion filtering functionality.
 *
 * This trait handles filtering of discovered items based on
 * exclusion lists from configuration.
 */
trait InteractsWithExclusions
{
    /**
     * Filter items by exclusion list.
     *
     * Removes items that match any entry in the exclusion list.
     *
     * @param  array<string>  $items  The items to filter
     * @param  array<string>  $exclusions  The exclusion list (class names or patterns)
     * @return array<string> Filtered array of items
     */
    protected function filterExclusions(array $items, array $exclusions): array
    {
        if (empty($exclusions)) {
            return $items;
        }

        return array_filter($items, function ($item) use ($exclusions) {
            foreach ($exclusions as $excluded) {
                if (str_contains($item, class_basename($excluded))) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Get exclusion list from configuration.
     *
     * Must be implemented by consuming class to provide
     * the appropriate config key.
     *
     * @return array<string> Array of excluded class names or patterns
     */
    abstract protected function getExclusionList(): array;
}
