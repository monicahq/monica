<?php

namespace Tests\Unit\Domains\Contact\ManageNotes\Web\ViewHelpers;

use App\Domains\Contact\ManageNotes\Web\ViewHelpers\ModuleNotesViewHelper;
use App\Domains\Contact\ManageNotes\Web\ViewHelpers\NotesIndexViewHelper;
use App\Models\Contact;
use App\Models\Emotion;
use App\Models\Note;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class NotesIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        Note::factory()->create([
            'contact_id' => $contact->id,
        ]);
        $emotion = Emotion::factory()->create([
            'account_id' => $contact->vault->account_id,
        ]);
        $user = User::factory()->create();

        $array = NotesIndexViewHelper::data($contact, Note::all(), $user);

        $this->assertEquals(
            4,
            count($array)
        );

        $this->assertArrayHasKey('contact', $array);
        $this->assertArrayHasKey('notes', $array);
        $this->assertArrayHasKey('emotions', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                0 => [
                    'id' => $emotion->id,
                    'name' => $emotion->name,
                    'type' => $emotion->type,
                ],
            ],
            $array['emotions']->toArray()
        );

        $this->assertEquals(
            [
                'name' => 'John Doe',
            ],
            $array['contact']
        );

        $this->assertEquals(
            [
                'store' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/notes',
                'contact' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id,
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $contact = Contact::factory()->create();
        $user = User::factory()->create();
        $note = Note::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $collection = ModuleNotesViewHelper::dto($contact, $note, $user);

        $this->assertEquals(
            [
                'id' => $note->id,
                'body' => $note->body,
                'body_excerpt' => null,
                'show_full_content' => false,
                'title' => $note->title,
                'emotion' => null,
                'author' => null,
                'written_at' => 'Jan 01, 2018',
                'url' => [
                    'update' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/notes/'.$note->id,
                    'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/notes/'.$note->id,
                ],
            ],
            $collection
        );
    }
}
