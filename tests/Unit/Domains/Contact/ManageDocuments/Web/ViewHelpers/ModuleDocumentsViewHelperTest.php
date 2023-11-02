<?php

namespace Tests\Unit\Domains\Contact\ManageDocuments\Web\ViewHelpers;

use App\Domains\Contact\ManageDocuments\Web\ViewHelpers\ModuleDocumentsViewHelper;
use App\Models\Contact;
use App\Models\File;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class ModuleDocumentsViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        config(['services.uploadcare.public_key' => '123']);

        $contact = Contact::factory()->create();
        File::factory()->create([
            'vault_id' => $contact->vault_id,
        ]);

        $array = ModuleDocumentsViewHelper::data($contact);

        $this->assertEquals(
            4,
            count($array)
        );

        $this->assertArrayHasKey('documents', $array);
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
                'store' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/documents',
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
        ]);

        $array = ModuleDocumentsViewHelper::dto($file, $contact);

        $this->assertEquals(
            [
                'id' => $file->id,
                'name' => $file->name,
                'mime_type' => $file->mime_type,
                'size' => '123B',
                'url' => [
                    'download' => $file->cdn_url,
                    'destroy' => env('APP_URL').'/vaults/'.$contact->vault->id.'/contacts/'.$contact->id.'/documents/'.$file->id,
                ],
            ],
            $array
        );
    }
}
