<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const array SUPPLIED_ORDER = [
        'basic-contact',
        'essentials',
        'friends-and-family',
        'professional-network',
        'full-portrait',
    ];

    public function up(): void
    {
        $vaults = DB::table('vaults')
            ->whereNotExists(function ($query): void {
                $query->from('vault_default_templates')
                    ->whereColumn('vault_default_templates.vault_id', 'vaults.id')
                    ->where('vault_default_templates.applies_to', 'contact');
            })
            ->get(['id', 'account_id']);

        foreach ($vaults as $vault) {
            $versionId = $this->startingVersionId($vault->account_id);

            if ($versionId === null) {
                continue;
            }

            DB::table('vault_default_templates')->insert([
                'account_id' => $vault->account_id,
                'vault_id' => $vault->id,
                'applies_to' => 'contact',
                'entity_type_id' => null,
                'structure_version_id' => $versionId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function startingVersionId(int $accountId): ?int
    {
        $templates = DB::table('templates')
            ->join('structures', function ($join): void {
                $join->on('structures.owner_id', '=', 'templates.id')
                    ->where('structures.owner_type', 'App\\Models\\Template');
            })
            ->where('templates.account_id', $accountId)
            ->whereNull('templates.deleted_at')
            ->where('structures.applies_to', 'contact')
            ->whereNotNull('structures.published_version_id')
            ->get(['templates.slug', 'templates.is_system', 'templates.is_active', 'templates.position', 'structures.published_version_id']);

        $chosen = $templates
            ->sortBy(fn (object $template): array => [
                $template->is_active ? 0 : 1,
                match (true) {
                    $template->is_system && in_array($template->slug, self::SUPPLIED_ORDER, true) => (int) array_search($template->slug, self::SUPPLIED_ORDER, true),
                    (bool) $template->is_system => count(self::SUPPLIED_ORDER),
                    default => count(self::SUPPLIED_ORDER) + 1,
                },
                $template->position,
            ])
            ->first();

        return $chosen === null ? null : (int) $chosen->published_version_id;
    }
};
