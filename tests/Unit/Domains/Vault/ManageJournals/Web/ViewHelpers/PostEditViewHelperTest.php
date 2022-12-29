<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Domains\Vault\ManageJournals\Web\ViewHelpers\PostEditViewHelper;
use App\Models\Contact;
use App\Models\Journal;
use App\Models\Post;
use App\Models\PostSection;
use App\Models\SliceOfLife;
use App\Models\User;
use App\Models\Vault;
use function env;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PostEditViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $slice = SliceOfLife::factory()->create([
            'journal_id' => $journal->id,
            'name' => 'slice name',
        ]);
        $post = Post::factory()->create([
            'journal_id' => $journal->id,
            'slice_of_life_id' => $slice->id,
        ]);
        PostSection::factory()->create([
            'post_id' => $post->id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $post->contacts()->attach($contact);

        $array = PostEditViewHelper::data($journal, $post, $user);

        $this->assertCount(13, $array);
        $this->assertEquals(
            $post->id,
            $array['id']
        );
        $this->assertEquals(
            $post->title,
            $array['title']
        );
        $this->assertEquals(
            [
                'id' => $slice->id,
                'name' => $slice->name,
                'url' => [
                    'show' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/slices/'.$slice->id,
                ],
            ],
            $array['slice']
        );
        $this->assertEquals(
            [
                'name' => $journal->name,
            ],
            $array['journal']
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $slice->id,
                    'name' => $slice->name,
                ],
            ],
            $array['slices']->toArray()
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'avatar' => $contact->avatar,
                    'url' => [
                        'show' => env('APP_URL').'/vaults/'.$vault->id.'/contacts/'.$contact->id,
                    ],
                ],
            ],
            $array['contacts']->toArray()
        );
        $this->assertEquals(
            [
                'update' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts/'.$post->id.'/update',
                'show' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts/'.$post->id,
                'slice_store' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts/'.$post->id.'/slices',
                'slice_reset' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts/'.$post->id.'/slices',
                'tag_store' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts/'.$post->id.'/tags',
                'back' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id,
                'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts/'.$post->id,
            ],
            $array['url']
        );
    }
}
