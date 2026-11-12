<?php

declare(strict_types=1);

namespace App\ViewModels\Settings\Account\PossessionTypes;

use App\Actions\ExportPossessionType;
use App\Helpers\PossessionTypeFile;
use App\Models\PossessionType;
use App\Models\User;

class PossessionTypeExportViewModel
{
    private ?string $json = null;

    public function __construct(
        private readonly User $user,
        private readonly PossessionType $possessionType,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function title(): string
    {
        return __('Export “:name”', ['name' => $this->possessionType->name]);
    }

    /** @return list<array{label: string, url?: string}> */
    public function breadcrumb(): array
    {
        return [
            ['label' => __('Possession types'), 'url' => route('settings.possessionTypes.index')],
            ['label' => $this->possessionType->name, 'url' => route('settings.possessionTypes.edit', $this->possessionType)],
            ['label' => __('Export')],
        ];
    }

    public function fileName(): string
    {
        return PossessionTypeFile::nameOf($this->possessionType);
    }

    public function json(): string
    {
        return $this->json ??= PossessionTypeFile::encode(new ExportPossessionType(
            user: $this->user,
            possessionType: $this->possessionType,
        )->execute());
    }

    public function downloadUrl(): string
    {
        return route('settings.possessionTypeExport.show', $this->possessionType);
    }
}
