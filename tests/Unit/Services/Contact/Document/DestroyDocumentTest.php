<?php
/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


namespace Tests\Unit\Services\Contact\Document;

use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\MissingParameterException;
use App\Services\Contact\Document\UploadDocument;
use App\Services\Contact\Document\DestroyDocument;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class DestroyDocumentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_a_document()
    {
        $contact = factory(Contact::class)->create([]);
        $document = $this->uploadDocument($contact);

        $request = [
            'account_id' => $document->account->id,
            'document_id' => $document->id,
        ];

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
        ]);

        $destroyDocumentService = new DestroyDocument;
        $bool = $destroyDocumentService->execute($request);

        $this->assertDatabaseMissing('documents', [
            'id' => $document->id,
        ]);

        Storage::disk('documents')->assertMissing('document.pdf');
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'document_id' => 2,
        ];

        $this->expectException(MissingParameterException::class);

        $destroyDocumentService = new DestroyDocument;
        $result = $destroyDocumentService->execute($request);
    }

    public function test_it_throws_a_document_doesnt_exist()
    {
        $document = factory(Document::class)->create([]);

        $request = [
            'account_id' => $document->account->id,
            'document_id' => 3,
        ];

        $this->expectException(ModelNotFoundException::class);

        $destroyDocumentService = new DestroyDocument;
        $document = $destroyDocumentService->execute($request);
    }

    private function uploadDocument($contact)
    {
        Storage::fake('documents');

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'document' => UploadedFile::fake()->image('document.pdf'),
        ];

        $uploadService = new UploadDocument;

        return $uploadService->execute($request);
    }
}
