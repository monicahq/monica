<?php

use App\Domains\Settings\ManageContactInformationTypes\Services\CreateContactInformationType;
use App\Models\ContactInformationType;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $map = collect(config('app.social_protocols'));
        $list = $map->pluck('name_translation_key');
        $map['Linkedin'] = $map['LinkedIn']; // Fix the typo in LinkedIn

        // Fix existing contact information types
        DB::table('contact_information_types')
            ->whereIn('name', $map->keys()->toArray())
            ->whereNull('name_translation_key')
            ->chunkById(100, function ($types) use ($map) {
                foreach ($types as $type) {
                    if (! isset($map[$type->name])) {
                        continue;
                    }
                    ContactInformationType::where('id', $type->id)->update([
                        'name' => null,
                        'name_translation_key' => $map[$type->name]['name_translation_key'],
                        'type' => $map[$type->name]['type'],
                    ]);
                }
            });

        // Ensure that the default contact information types are created
        User::where('is_account_administrator', true)->chunkById(100, function ($users) use ($map, $list) {
            foreach ($users as $user) {
                $types = $user->account->contactInformationTypes
                    ->where(fn ($type) => $type->type !== 'email' && $type->type !== 'phone')
                    ->pluck('name_translation_key');
                $list->diff($types)
                    ->each(function ($name) use ($user, $map) {
                        (new CreateContactInformationType)->execute([
                            'account_id' => $user->account->id,
                            'author_id' => $user->id,
                            'name' => null,
                            'name_translation_key' => $map[$name]['name_translation_key'],
                            'type' => $map[$name]['type'],
                        ]);
                    });
            }
        });
    }
};
