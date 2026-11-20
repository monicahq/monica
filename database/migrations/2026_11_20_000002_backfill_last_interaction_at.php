<?php

declare(strict_types=1);

use App\Jobs\RecountLastInTouchOfType;
use App\Models\LifeEventType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const array NEWS_ABOUT_ONE_PERSON = [
        'marriage',
        'birth',
        'separation',
        'interview',
        'new_job',
        'class',
        'training',
        'graduation',
        'medical_appointment',
        'therapy',
        'illness',
        'hospital_stay',
        'move',
        'home_improvement',
        'major_purchase',
        'household_task',
        'reading',
        'reflection',
        'decision',
        'achievement',
        'memory',
        'spiritual_event',
    ];

    public function up(): void
    {
        DB::table('life_event_types')
            ->where('is_system', true)
            ->whereIn('key', self::NEWS_ABOUT_ONE_PERSON)
            ->update(['updates_last_in_touch' => false]);

        LifeEventType::query()
            ->withTrashed()
            ->where('updates_last_in_touch', true)
            ->whereIn('id', DB::table('life_events')->select('life_event_type_id'))
            ->each(function (LifeEventType $lifeEventType): void {
                RecountLastInTouchOfType::dispatch(lifeEventType: $lifeEventType)
                    ->onQueue('low')
                    ->delay(now()->addMinutes(5));
            });
    }
};
