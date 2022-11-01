<?php

namespace Tests\Unit\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions;

use App\Domains\Contact\ManageContactFeed\Web\ViewHelpers\Actions\ActionFeedLabelAssigned;
use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\Label;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ActionFeedLabelAssignedTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create([
            'first_name' => 'John',
            'last_name' => 'Doe',
        ]);
        $label = Label::factory()->create([
            'vault_id' => $contact->vault_id,
            'name' => 'label',
        ]);
        $feedItem = ContactFeedItem::factory()->create([
            'contact_id' => $contact->id,
            'action' => ContactFeedItem::ACTION_LABEL_ASSIGNED,
            'description' => 'label',
        ]);
        $label->feedItem()->save($feedItem);

        $array = ActionFeedLabelAssigned::data($feedItem);

        $this->assertEquals(
            [
                'label' => [
                    'object' => [
                        'id' => $label->id,
                        'name' => 'label',
                        'bg_color' => 'bg-zinc-200',
                        'text_color' => 'text-zinc-700',
                        'url' => env('APP_URL').'/vaults/'.$contact->vault_id.'/contacts/labels/'.$label->id,
                    ],
                    'description' => 'label',
                ],
                'contact' => [
                    'id' => $contact->id,
                    'name' => 'John Doe',
                    'age' => null,
                    'avatar' => $contact->avatar,
                    'url' => env('APP_URL').'/vaults/'.$contact->vault_id.'/contacts/'.$contact->id,
                ],
            ],
            $array
        );
    }
}
