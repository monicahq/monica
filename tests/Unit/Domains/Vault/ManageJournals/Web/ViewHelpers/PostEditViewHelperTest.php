<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Domains\Vault\ManageJournals\Web\ViewHelpers\PostEditViewHelper;
use App\Models\Contact;
use App\Models\File;
use App\Models\Journal;
use App\Models\JournalMetric;
use App\Models\Post;
use App\Models\PostMetric;
use App\Models\PostSection;
use App\Models\SliceOfLife;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

use function env;

class PostEditViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        config(['services.uploadcare.public_key' => '123']);

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
        $file = File::factory()->create([
            'vault_id' => $contact->vault_id,
            'size' => 123,
            'type' => File::TYPE_PHOTO,
        ]);
        $file->fileable_id = $post->id;
        $file->fileable_type = Post::class;
        $file->save();

        $array = PostEditViewHelper::data($journal, $post, $user);

        $this->assertCount(17, $array);
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
            '123',
            $array['uploadcare']['publicKey']
        );
        $this->assertTrue(
            $array['canUploadFile']
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
                    'id' => $file->id,
                    'name' => $file->name,
                    'size' => '123B',
                    'mime_type' => 'avatar',
                    'url' => [
                        'show' => 'https://ucarecdn.com/'.$file->uuid.'/-/scale_crop/75x75/smart/-/format/auto/-/quality/smart_retina/',
                        'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts/'.$post->id.'/photos/'.$file->id,
                    ],
                ],
            ],
            $array['photos']->toArray()
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
                'upload_photo' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts/'.$post->id.'/photos',
                'back' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id,
                'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts/'.$post->id,
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_details_of_the_post_metric(): void
    {
        $vault = Vault::factory()->create();
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $post = Post::factory()->create([
            'journal_id' => $journal->id,
        ]);
        $journalMetric = JournalMetric::factory()->create([
            'journal_id' => $journal->id,
        ]);
        $postMetric = PostMetric::factory()->create([
            'post_id' => $post->id,
            'value' => 123,
            'label' => 'label',
            'journal_metric_id' => $journalMetric->id,
        ]);

        $array = PostEditViewHelper::dtoPostMetric($postMetric);

        $this->assertEquals(
            [
                'id' => $postMetric->id,
                'value' => 123,
                'label' => 'label',
                'url' => [
                    'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/posts/'.$post->id.'/metrics/'.$postMetric->id,
                ],
            ],
            $array
        );
    }
}
