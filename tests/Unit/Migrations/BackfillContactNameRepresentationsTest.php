<?php

declare(strict_types=1);

namespace Tests\Unit\Migrations;

use App\Enums\ContactNameKindEnum;
use App\Models\Contact;
use App\Models\ContactNameRepresentation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class BackfillContactNameRepresentationsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_gives_a_named_contact_made_before_the_feature_one_written_way_of_writing_its_name_and_shows_it(): void
    {
        $contact = $this->contactMadeBeforeTheFeature(['prefix' => 'Mr', 'first_name' => 'Stanley', 'last_name' => 'Hudson']);
        $nameBefore = $contact->name;

        $this->migration()->up();

        $contact->refresh();
        $representation = $contact->displayNameRepresentation;
        $this->assertSame(ContactNameKindEnum::Written, $representation->kind);
        $this->assertSame('Stanley', $representation->given_name);
        $this->assertSame('Hudson', $representation->family_name);
        $this->assertSame('Mr', $representation->prefix);
        $this->assertSame(1, $representation->position);
        $this->assertSame('hudson stanley', $representation->sort_name);
        $this->assertSame($nameBefore, $contact->name);
    }

    #[Test]
    public function it_gives_a_nameless_contact_nothing(): void
    {
        $contact = $this->contactMadeBeforeTheFeature(['first_name' => null, 'last_name' => null, 'nickname' => 'Toby']);

        $this->migration()->up();

        $this->assertNull($contact->refresh()->display_name_representation_id);
        $this->assertSame(0, ContactNameRepresentation::query()->where('contact_id', $contact->id)->count());
    }

    #[Test]
    public function it_adds_nothing_the_second_time_it_runs(): void
    {
        $contact = $this->contactMadeBeforeTheFeature(['first_name' => 'Oscar', 'last_name' => 'Martinez']);

        $this->migration()->up();
        $this->migration()->up();

        $this->assertSame(1, ContactNameRepresentation::query()->where('contact_id', $contact->id)->count());
    }

    #[Test]
    public function it_queues_no_work(): void
    {
        $this->contactMadeBeforeTheFeature(['first_name' => 'Phyllis', 'last_name' => 'Vance']);
        Queue::fake();

        $this->migration()->up();

        Queue::assertNothingPushed();
    }

    /** @param array<string, mixed> $attributes */
    private function contactMadeBeforeTheFeature(array $attributes): Contact
    {
        $contact = Contact::factory()->create($attributes);

        DB::table('contacts')->where('id', $contact->id)->update(['display_name_representation_id' => null]);
        DB::table('contact_name_representations')->where('contact_id', $contact->id)->delete();

        return $contact->refresh();
    }

    private function migration(): Migration
    {
        return require database_path('migrations/2026_10_06_000002_backfill_contact_name_representations.php');
    }
}
