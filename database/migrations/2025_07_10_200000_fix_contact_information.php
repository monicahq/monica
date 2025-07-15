<?php

use App\Models\ContactInformationType;
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
        $map['Linkedin'] = $map['LinkedIn']; // Fix the typo in LinkedIn

        DB::table('contact_information_types')
            ->whereIn('name', $map->keys()->toArray())
            ->whereNull('name_translation_key')
            ->chunk(100, function ($types) use ($map) {

                foreach ($types as $type) {
                    ContactInformationType::where('id', $type->id)->update([
                        'name_translation_key' => $map[$type->name]['name_translation_key'],
                        'type' => $map[$type->name]['type'],
                    ]);
                }
            });
    }
};
