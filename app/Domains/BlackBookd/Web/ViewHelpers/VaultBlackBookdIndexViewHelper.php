<?php

namespace App\Domains\BlackBookd\Web\ViewHelpers;

use App\Models\BlackBookdDatingPipeline;
use App\Models\BlackBookdEncounter;
use App\Models\BlackBookdHealthLogEntry;
use App\Models\User;
use App\Models\Vault;

class VaultBlackBookdIndexViewHelper
{
    public static function data(Vault $vault, User $user): array
    {
        return [
            'statistics' => [
                'encounters' => $vault->blackbookdEncounters()->count(),
                'health_entries' => $vault->blackbookdHealthLogEntries()->count(),
                'pipeline' => $vault->blackbookdDatingPipelines()->count(),
            ],
            'recent_encounters' => $vault->blackbookdEncounters()->with('contacts')->orderByDesc('occurred_at')->take(10)->get()
                ->map(fn (BlackBookdEncounter $e) => [
                    'id' => $e->id,
                    'occurred_at' => $e->occurred_at->toIso8601String(),
                    'location_label' => $e->location_label,
                    'contacts' => $e->contacts->pluck('name'),
                ]),
            'recent_health' => $vault->blackbookdHealthLogEntries()->orderByDesc('occurred_on')->take(10)->get()
                ->map(fn (BlackBookdHealthLogEntry $h) => [
                    'id' => $h->id,
                    'entry_type' => $h->entry_type,
                    'occurred_on' => $h->occurred_on->format('Y-m-d'),
                ]),
            'partner_lookback_days' => $user->account->blackbookd_partner_lookback_days,
            'url' => [
                'pipeline' => route('vault.blackbookd.pipeline', ['vault' => $vault->id]),
            ],
        ];
    }

    public static function pipelineBoard(Vault $vault): array
    {
        $pipelines = $vault->blackbookdDatingPipelines()->with('contact')->get();
        $columns = [];
        foreach (BlackBookdDatingPipeline::STAGES as $stage) {
            $columns[$stage] = $pipelines->where('stage', $stage)->map(fn (BlackBookdDatingPipeline $p) => [
                'id' => $p->id,
                'handle' => $p->handle,
                'contact_name' => $p->contact->name,
                'metro_location' => $p->metro_location,
                'next_step' => $p->next_step,
                'long_term_fit_score' => $p->long_term_fit_score,
                'boundary_held_do_not_reengage' => $p->boundary_held_do_not_reengage,
                'last_message_at' => $p->last_message_at?->toIso8601String(),
            ])->values();
        }

        return [
            'stages' => BlackBookdDatingPipeline::STAGES,
            'columns' => $columns,
        ];
    }
}