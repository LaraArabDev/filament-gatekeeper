<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Resources\PermissionResource\Pages;

use Filament\Resources\Pages\ListRecords;
use LaraArabDev\FilamentGatekeeper\Resources\PermissionResource;

class ListPermissions extends ListRecords
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            //
        ];
    }
}

