<?php

namespace Tests\Feature\Domains\Vault\ManageJournals\Web\Controllers;

use App\Models\Contact;
use App\Models\ContactFeedItem;
use App\Models\Journal;
use App\Models\Post;
use App\Models\PostSection;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PostControllerTest extends TestCase
{
    use DatabaseTransactions;

    #[Test]
    public function it_does_not_spam_the_activity_log_when_re_saving_the_same_tagged_contacts(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $post = Post::factory()->create([
            'journal_id' => $journal->id,
        ]);
        $postSection = PostSection::factory()->create([
            'post_id' => $post->id,
            'content' => 'this is a content',
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $payload = [
            'title' => 'title',
            'date' => now()->format('Y-m-d'),
            'sections' => [
                [
                    'id' => $postSection->id,
                    'content' => 'this is a content',
                ],
            ],
            'contacts' => [
                ['id' => $contact->id],
            ],
        ];

        $route = route('post.update', [
            'vault' => $vault->id,
            'journal' => $journal->id,
            'post' => $post->id,
        ]);

        // first edit: the contact is newly tagged, so it should be logged once
        $this->actingAs($regis)->putJson($route, $payload)->assertOk();

        $this->assertDatabaseHas('contact_post', [
            'contact_id' => $contact->id,
            'post_id' => $post->id,
        ]);
        $this->assertSame(1, ContactFeedItem::where([
            'contact_id' => $contact->id,
            'action' => ContactFeedItem::ACTION_ADDED_TO_POST,
        ])->count());

        // re-saving the post with the exact same tagged contact must not
        // create a new "added to post" activity entry
        $this->actingAs($regis)->putJson($route, $payload)->assertOk();

        $this->assertSame(1, ContactFeedItem::where([
            'contact_id' => $contact->id,
            'action' => ContactFeedItem::ACTION_ADDED_TO_POST,
        ])->count());
    }
}
