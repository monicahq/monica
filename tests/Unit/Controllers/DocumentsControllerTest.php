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


namespace Tests\Unit\Controllers;

use Tests\FeatureTestCase;
use App\Models\Contact\Contact;
use App\Models\Contact\Document;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class DocumentsControllerTest extends FeatureTestCase
{
    use DatabaseTransactions;

    protected $jsonStructure = [
        'id',
        'object',
        'original_filename',
        'new_filename',
        'filesize',
        'type',
        'number_of_downloads',
        'contact',
        'created_at',
        'updated_at',
    ];

    public function test_it_gets_the_list_of_documents()
    {
        $user = $this->signin();

        $contact = factory(Contact::class)->create([
            'account_id' => $user->account_id,
        ]);

        factory(Document::class, 100)->create([
            'account_id' => $user->account_id,
            'contact_id' => $contact->id,
        ]);

        $response = $this->json('GET', '/people/'.$contact->hashID().'/documents');

        $response->assertStatus(200);

        $response->assertJsonStructure([
            'data' => [
                '*' => $this->jsonStructure,
            ],
        ]);

        $this->assertCount(
            100,
            $response->decodeResponseJson()['data']
        );
    }
}
