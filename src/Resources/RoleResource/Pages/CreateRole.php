<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
