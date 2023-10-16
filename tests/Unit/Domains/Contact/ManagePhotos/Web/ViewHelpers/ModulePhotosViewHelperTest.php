<?php

namespace Tests\Unit\Domains\Contact\ManagePhotos\Web\ViewHelpers;

use App\Domains\Contact\ManagePhotos\Web\ViewHelpers\ModulePhotosViewHelper;
use App\Models\Contact;
use App\Models\File;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModulePhotosViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        config(['services.uploadcare.public_key' => '123']);

        $contact = Contact::factory()->create();
        $file = File::factory()->create([
            'vault_id' => $contact->vault_id,
        ]);
        $contact->files()->save($file);

        $array = ModulePhotosViewHelper::data($contact);

        $this->assertEquals(
            4,
            count($array)
        );

        $this->assertArrayHasKey('photos', $array);
        $this->assertArrayHasKey('uploadcare', $array);
        $this->assertArrayHasKey('canUploadFile', $array);
        $this->assertArrayHasKey('url', $array);

        $this->assertEquals(
            '123',
            $array['uploadcare']['publicKey']
        );
        $this->assertFalse($array['canUploadFile']);
        $this->assertEquals(
            [
                'index' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/photos',
                'store' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/photos',
            ],
            $array['url']
        );
    }

    /** @test */
    public function it_gets_the_data_transfer_object(): void
    {
        $contact = Contact::factory()->create();
        $file = File::factory()->create([
            'vault_id' => $contact->vault_id,
            'size' => 123,
            'uuid' => 123,
        ]);

        $array = ModulePhotosViewHelper::dto($file, $contact);

        $this->assertEquals(
            [
                'id' => $file->id,
                'name' => $file->name,
                'mime_type' => $file->mime_type,
                'size' => '123B',
                'url' => [
                    'display' => 'https://ucarecdn.com/123/-/scale_crop/300x300/smart/-/format/auto/-/quality/smart_retina/',
                    'download' => $file->cdn_url,
                    'show' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/photos/'.$file->id,
                    'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/photos/'.$file->id,
                ],
            ],
            $array
        );
    }
}
