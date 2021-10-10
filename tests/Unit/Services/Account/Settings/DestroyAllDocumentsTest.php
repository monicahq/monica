<?php

namespace Tests\Unit\Services\Account\Settings;

use Tests\TestCase;
use App\Models\Contact\Contact;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Document\UploadDocument;
use App\Services\Account\Settings\DestroyAllDocuments;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DestroyAllDocumentsTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_destroys_all_documents()
    {
        Storage::fake();

        $contact = factory(Contact::class)->create([]);

        $documents = [];
        for ($i = 0; $i < 2; $i++) {
            $documents[] = $this->uploadDocument($contact);
        }

        $request = [
            'account_id' => $contact->account_id,
        ];

        app(DestroyAllDocuments::class)->execute($request);

        $this->assertDatabaseMissing('documents', [
            'account_id' => $contact->account_id,
        ]);

        foreach ($documents as $document) {
            Storage::disk('public')->assertMissing($document->new_filename);
        }
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [
        ];

        $this->expectException(ValidationException::class);

        app(DestroyAllDocuments::class)->execute($request);
    }

    private function uploadDocument($contact)
    {
        $request = [
            'account_id' => $contact->account_id,
            'contact_id' => $contact->id,
            'document' => UploadedFile::fake()->image('document.pdf'),
        ];

        $document = app(UploadDocument::class)->execute($request);

        Storage::disk('public')->assertExists($document->new_filename);

        return $document;
    }
}
