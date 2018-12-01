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
use App\Models\Account\Account;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use App\Exceptions\MissingParameterException;
use App\Services\Contact\Document\UploadDocument;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UploadDocumentTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_uploads_a_document()
    {
        Storage::fake('documents');

        $contact = factory(Contact::class)->create([]);

        $file = UploadedFile::fake()->image('document.pdf');

        $request = [
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'document' => $file,
        ];

        $uploadService = new UploadDocument;
        $document = $uploadService->execute($request);

        $this->assertDatabaseHas('documents', [
            'id' => $document->id,
            'account_id' => $contact->account->id,
            'contact_id' => $contact->id,
            'type' => 'pdf',
        ]);

        $this->assertInstanceOf(
            Document::class,
            $document
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [
            'account_id' => 1,
            'contact_id' => 2,
        ];

        $this->expectException(MissingParameterException::class);

        $uploadService = new UploadDocument;
        $document = $uploadService->execute($request);
    }

    public function test_it_throws_an_exception_if_contact_does_not_exist()
    {
        Storage::fake('documents');

        $account = factory(Account::class)->create();
        $contact = factory(Contact::class)->create([]);

        $request = [
            'account_id' => $account->id,
            'contact_id' => 2,
            'document' => UploadedFile::fake()->image('document.pdf'),
        ];

        $this->expectException(ModelNotFoundException::class);

        $uploadService = (new UploadDocument)->execute($request);
    }
}
