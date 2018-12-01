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


namespace Tests\Unit\Services\Account;

use Tests\TestCase;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\MissingParameterException;
use App\Services\Account\DestroyAllDocuments;
use App\Services\Contact\Document\UploadDocument;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DestroyAllDocumentsTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_destroys_all_documents()
    {
        $contact = factory(Contact::class)->create([]);

        for ($i = 0; $i < 10; $i++) {
            $this->uploadDocument($contact);
        }

        $request = [
            'account_id' => $contact->account->id,
        ];

        $destroyAllDocumentsService = new DestroyAllDocuments;
        $bool = $destroyAllDocumentsService->execute($request);

        $this->assertDatabaseMissing('documents', [
            'account_id' => $contact->account->id,
        ]);

        Storage::disk('documents')->assertMissing('document.pdf');
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
        ];

        $this->expectException(MissingParameterException::class);

        $destroyAllDocumentsService = new DestroyAllDocuments;
        $result = $destroyAllDocumentsService->execute($request);
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
