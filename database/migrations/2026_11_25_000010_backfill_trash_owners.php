<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->giveTheMergesWhatTheyDropped();
        $this->startTheRetentionPeriodNow();
    }

    private function giveTheMergesWhatTheyDropped(): void
    {
        DB::table('contact_merges')->whereNull('undone_at')->orderBy('id')->each(function (object $merge): void {
            $ledger = collect(json_decode((string) $merge->ledger, true) ?: []);
            $owner = ['deleted_with_type' => 'App\Models\ContactMerge', 'deleted_with_id' => $merge->id];

            DB::table('relationships')
                ->whereNotNull('deleted_at')
                ->whereIn('id', $ledger->where('kind', 'relationship_dropped')->pluck('id')->all())
                ->update($owner);

            DB::table('group_members')
                ->whereNotNull('deleted_at')
                ->whereIn('id', $ledger->where('kind', 'membership_folded')->pluck('dropped')->all())
                ->update($owner);
        });
    }

    private function startTheRetentionPeriodNow(): void
    {
        $now = now();

        DB::table('contacts')
            ->whereNotNull('deleted_at')
            ->where('deleted_with_vault', false)
            ->whereNotIn('id', DB::table('contact_merges')->whereNull('undone_at')->select('merged_contact_id'))
            ->update(['deleted_at' => $now]);

        foreach (['entities', 'groups'] as $table) {
            DB::table($table)
                ->whereNotNull('deleted_at')
                ->where('deleted_with_vault', false)
                ->update(['deleted_at' => $now]);
        }

        foreach (['relationships', 'life_events', 'reminders', 'group_members', 'group_notes'] as $table) {
            DB::table($table)
                ->whereNotNull('deleted_at')
                ->whereNull('deleted_with_type')
                ->update(['deleted_at' => $now]);
        }
    }
};
