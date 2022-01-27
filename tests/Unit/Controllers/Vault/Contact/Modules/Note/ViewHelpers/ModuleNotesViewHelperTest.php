<?php

namespace Tests\Unit\Controllers\Vault\Contact\Modules\Note\ViewHelpers;

use function env;
use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Note;
use App\Models\Contact;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Http\Controllers\Vault\Contact\Modules\Note\ViewHelpers\ModuleNotesViewHelper;

class ModuleNotesViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();
        Note::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $array = ModuleNotesViewHelper::data($contact);

        $this->assertEquals(
            2,
            count($array)
        );

        $this->assertArrayHasKey('notes', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            [
                'store' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/notes',
                'index' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/notes',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        Carbon::setTestNow(Carbon::create(2018, 1, 1));
        $contact = Contact::factory()->create();
        $note = Note::factory()->create([
            'contact_id' => $contact->id,
        ]);

        $collection = ModuleNotesViewHelper::dto($contact, $note);

        $this->assertEquals(
            [
                'id' => $note->id,
                'body' => $note->body,
                'body_excerpt' => null,
                'show_full_content' => false,
                'title' => $note->title,
                'author' => $note->author->name,
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
