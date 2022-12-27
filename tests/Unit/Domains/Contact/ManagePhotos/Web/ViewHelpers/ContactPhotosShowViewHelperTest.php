<?php

namespace Tests\Unit\Domains\Contact\ManagePhotos\Web\ViewHelpers;

use App\Domains\Contact\ManagePhotos\Web\ViewHelpers\ContactPhotosShowViewHelper;
use App\Models\Contact;
use App\Models\File;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ContactPhotosShowViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        $contact = Contact::factory()->create();
        $file = File::factory()->create([
            'vault_id' => $contact->vault_id,
            'size' => 123,
            'uuid' => 123,
        ]);
        $contact->files()->save($file);

        $array = ContactPhotosShowViewHelper::data($file, $contact);

        $this->assertEquals(
            6,
            count($array)
        );

        $this->assertEquals(
            [
                'contact' => [
                    'name' => $contact->name,
                ],
                'id' => $file->id,
                'name' => $file->name,
                'mime_type' => $file->mime_type,
                'size' => '123B',
                'url' => [
                    'display' => 'https://ucarecdn.com/123/-/resize/1700x/-/format/auto/-/quality/smart_retina/',
                    'download' => $file->cdn_url,
                    'show' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id,
                    'index' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/photos',
                    'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/photos/'.$file->id,
                ],
            ],
            $array
        );
    }
}
