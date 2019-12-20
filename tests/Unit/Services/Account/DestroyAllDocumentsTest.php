<?php

namespace Tests\Unit\Services\Account;

use Tests\TestCase;
use App\Models\Contact\Contact;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Services\Account\DestroyAllDocuments;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Document\UploadDocument;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DestroyAllDocumentsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_all_documents()
    {
        Storage::fake();

        $contact = factory(Contact::class)->create([]);

        $documents = [];
        for ($i = 0; $i < 10; $i++) {
            $documents[] = $this->uploadDocument($contact);
        }

        $request = [
            'account_id' => $contact->account->id,
        ];

        app(DestroyAllDocuments::class)->execute($request);

        $this->assertDatabaseMissing('documents', [
            'account_id' => $contact->account->id,
        ]);

        foreach ($documents as $document) {
            Storage::disk('public')->assertMissing($document->new_filename);
        }
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
        ];

        $this->expectException(ValidationException::class);

        app(DestroyAllDocuments::class)->execute($request);
    }

    private function uploadDocument($contact)
    {
        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'document' => UploadedFile::fake()->image('document.pdf'),
        ];

        $document = app(UploadDocument::class)->execute($request);

        Storage::disk('public')->assertExists($document->new_filename);

        return $document;
    }
}
