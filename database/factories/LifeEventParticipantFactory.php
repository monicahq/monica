<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Contact;
use App\Models\LifeEvent;
use App\Models\LifeEventParticipant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Override;

/** @extends Factory<LifeEventParticipant> */
class LifeEventParticipantFactory extends Factory
{
    protected $model = LifeEventParticipant::class;

    /** @return array<string, mixed> */
    #[Override]
    public function definition(): array
    {
        return [
            'life_event_id' => LifeEvent::factory(),
            'account_id' => fn (array $attributes): int => LifeEvent::query()->findOrFail($attributes['life_event_id'])->account_id,
            'participant_type' => Contact::class,
            'participant_id' => fn (array $attributes): int => Contact::factory()->create([
                'account_id' => $attributes['account_id'],
                'vault_id' => LifeEvent::query()->findOrFail($attributes['life_event_id'])->vault_id,
            ])->id,
            'role' => null,
            'notes' => null,
            'position' => 0,
        ];
    }
}
