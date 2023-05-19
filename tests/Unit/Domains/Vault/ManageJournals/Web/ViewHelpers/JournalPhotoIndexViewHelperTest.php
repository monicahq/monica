<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Domains\Vault\ManageJournals\Web\ViewHelpers\JournalPhotoIndexViewHelper;
use App\Models\File;
use App\Models\Journal;
use App\Models\Post;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class JournalPhotoIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $journal = Journal::factory()->create([
            'name' => 'My Journal',
            'description' => 'My Journal Description',
        ]);
        $post = Post::factory()->create([
            'journal_id' => $journal->id,
            'title' => 'My Post',
            'written_at' => '2020-01-01',
        ]);
        $file = File::factory()->create([
            'vault_id' => $journal->vault_id,
            'size' => 123,
            'uuid' => 123,
            'type' => File::TYPE_PHOTO,
            'fileable_id' => $post->id,
            'fileable_type' => Post::class,
        ]);

        $array = JournalPhotoIndexViewHelper::data($journal);
        $this->assertCount(5, $array);
        $this->assertEquals(
            $journal->id,
            $array['id']
        );
        $this->assertEquals(
            'My Journal',
            $array['name']
        );
        $this->assertEquals(
            'My Journal Description',
            $array['description']
        );
        $this->assertEquals(
            [
                0 => [
                    'id' => $file->id,
                    'name' => $file->name,
                    'url' => [
                        'post' => env('APP_URL').'/vaults/'.$post->journal->vault_id.'/journals/'.$post->journal_id.'/posts/'.$post->id,
                        'display' => 'https://ucarecdn.com/'.$file->uuid.'/-/scale_crop/200x200/smart/-/format/auto/-/quality/smart_retina/',
                    ],
                ],
            ],
            $array['photos']->toArray()
        );
        $this->assertEquals(
            [
                'show' => env('APP_URL').'/vaults/'.$journal->vault->id.'/journals/'.$journal->id,
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_photo_data_transfer_object(): void
    {
        $file = File::factory()->create([
            'size' => 123,
            'uuid' => 123,
        ]);
        $post = Post::factory()->create();
        $post->files()->save($file);

        $array = JournalPhotoIndexViewHelper::dto($file, $post);

        $this->assertEquals(
            [
                'id' => $file->id,
                'name' => $file->name,
                'url' => [
                    'post' => env('APP_URL').'/vaults/'.$post->journal->vault_id.'/journals/'.$post->journal_id.'/posts/'.$post->id,
                    'display' => 'https://ucarecdn.com/'.$file->uuid.'/-/scale_crop/200x200/smart/-/format/auto/-/quality/smart_retina/',
                ],
            ],
            $array
        );
    }
}
