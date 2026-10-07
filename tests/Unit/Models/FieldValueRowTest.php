<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValueRow;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldValueRowTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_reads_the_same_way_twice_when_two_repetitions_hold_one_place(): void
    {
        $user = User::factory()->create();
        $contact = Contact::factory()->create();
        $field = Field::factory()->create(['is_repeatable' => true]);

        $first = FieldValueRow::factory()->create([
            'account_id' => $contact->vault->account_id,
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'position' => 1,
        ]);

        $second = FieldValueRow::factory()->create([
            'account_id' => $contact->vault->account_id,
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'field_id' => $field->id,
            'position' => 1,
        ]);

        $read = fn (): array => FieldValueRow::query()
            ->where('valuable_type', $contact->getMorphClass())
            ->where('valuable_id', $contact->id)
            ->where('field_id', $field->id)
            ->orderBy('position')
            ->orderBy('id')
            ->pluck('id')
            ->all();

        $this->assertSame([$first->id, $second->id], $read());
        $this->assertSame($read(), $read());
    }

    #[Test]
    public function it_moves_the_record_it_belongs_to_when_a_repetition_is_added_or_removed(): void
    {
        $contact = Contact::factory()->create();
        $field = Field::factory()->create();
        $contact->forceFill(['updated_at' => CarbonImmutable::now()->subYear()])->saveQuietly();

        $row = FieldValueRow::factory()->create([
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'field_id' => $field->id,
            'position' => 1,
        ]);

        $this->assertTrue($contact->refresh()->updated_at->isToday());

        $contact->forceFill(['updated_at' => CarbonImmutable::now()->subYear()])->saveQuietly();

        $row->delete();

        $this->assertTrue($contact->refresh()->updated_at->isToday());
    }

    #[Test]
    public function it_does_not_save_the_record_while_its_kind_is_set_aside(): void
    {
        $contact = Contact::factory()->create();
        $field = Field::factory()->create();
        $saves = 0;

        Contact::saved(function () use (&$saves): void {
            $saves++;
        });

        Model::withoutTouchingOn([Contact::class], fn () => FieldValueRow::factory()->create([
            'valuable_type' => $contact->getMorphClass(),
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'field_id' => $field->id,
            'position' => 1,
        ]));

        $this->assertSame(0, $saves);
    }
}
