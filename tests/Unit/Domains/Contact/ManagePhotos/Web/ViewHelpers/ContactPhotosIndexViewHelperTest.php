<?php

namespace Tests\Unit\Domains\Contact\ManagePhotos\Web\ViewHelpers;

use App\Domains\Contact\ManagePhotos\Web\ViewHelpers\ContactPhotosIndexViewHelper;
use App\Models\Contact;
use App\Models\File;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactPhotosIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        config(['services.uploadcare.public_key' => '123']);

        $contact = Contact::factory()->create();
        File::factory()->create([
            'vault_id' => $contact->vault_id,
            'size' => 123,
            'uuid' => 123,
        ]);

        $files = File::all();

        $array = ContactPhotosIndexViewHelper::data($files, $contact);

        $this->assertEquals(
            5,
            count($array)
        );

        $this->assertEquals(
            [
                'name' => $contact->name,
            ],
            $array['contact']
        );
        $this->assertEquals(
            '123',
            $array['uploadcare']['publicKey']
        );
        $this->assertTrue($array['canUploadFile']);
        $this->assertEquals(
            [
                'show' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id,
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

        $array = ContactPhotosIndexViewHelper::dto($file, $contact);

        $this->assertEquals(
            [
                'id' => $file->id,
                'name' => $file->name,
                'mime_type' => $file->mime_type,
                'size' => '123B',
                'url' => [
                    'display' => 'https://ucarecdn.com/123/-/scale_crop/400x400/smart/-/format/auto/-/quality/smart_retina/',
                    'download' => $file->cdn_url,
                    'show' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/photos/'.$file->id,
                    'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/photos/'.$file->id,
                ],
            ],
            $array
        );
    }
}
