<?php

declare(strict_types=1);

namespace LaraArabDev\FilamentGatekeeper\Resources\RoleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use LaraArabDev\FilamentGatekeeper\Models\Role;
use LaraArabDev\FilamentGatekeeper\Resources\RoleResource;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->hidden(function (): bool {
                    $record = $this->record;

                    return $record instanceof Role && $record->isSuperAdmin();
                }),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
