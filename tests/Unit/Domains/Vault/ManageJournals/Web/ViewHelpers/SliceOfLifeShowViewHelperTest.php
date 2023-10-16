<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Web\ViewHelpers;

use App\Domains\Vault\ManageJournals\Web\ViewHelpers\SliceOfLifeShowViewHelper;
use App\Models\File;
use App\Models\Journal;
use App\Models\SliceOfLife;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SliceOfLifeShowViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        config(['services.uploadcare.public_key' => '123']);
        $vault = Vault::factory()->create();
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'name',
        ]);
        $slice = SliceOfLife::factory()->create([
            'journal_id' => $journal->id,
            'name' => 'this is a title',
        ]);

        $array = SliceOfLifeShowViewHelper::data($slice);

        $this->assertCount(7, $array);
        $this->assertEquals(
            [
                'id' => $journal->id,
                'name' => 'name',
                'url' => [
                    'show' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id,
                ],
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
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        $vault = Vault::factory()->create();
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
            'name' => 'name',
        ]);
        $slice = SliceOfLife::factory()->create([
            'journal_id' => $journal->id,
            'name' => 'this is a title',
        ]);
        $file = File::factory()->create([
            'uuid' => '123',
        ]);
        $slice->file_cover_image_id = $file->id;
        $slice->save();

        $this->assertEquals(
            [
                'id' => $slice->id,
                'name' => 'this is a title',
                'description' => null,
                'date_range' => null,
                'cover_image' => 'https://ucarecdn.com/123/-/scale_crop/800x100/smart/-/format/auto/-/quality/smart_retina/',
                'url' => [
                    'show' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/slices/'.$slice->id,
                    'edit' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/slices/'.$slice->id.'/edit',
                    'update_cover_image' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/slices/'.$slice->id.'/cover',
                    'destroy_cover_image' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/slices/'.$slice->id.'/cover',
                    'destroy' => env('APP_URL').'/vaults/'.$vault->id.'/journals/'.$journal->id.'/slices/'.$slice->id,
                ],
            ],
            SliceOfLifeShowViewHelper::dtoSlice($slice)
        );
    }
}
