<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValueRow;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FieldValueRowTest extends TestCase
{
    use RefreshDatabase;

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
}
