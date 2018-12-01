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


namespace Tests\Api;

use Tests\ApiTestCase;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ApiMiscTest extends ApiTestCase
{
    use DatabaseTransactions;

    protected $jsonCountries = [
            'id',
            'iso',
            'name',
            'object',
    ];

    public function test_misc_get()
    {
        $user = $this->signin();

        $response = $this->json('GET', '/api/countries');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonCountries],
        ]);
        $response->assertJsonFragment([
            'DEU' => [
                'id' => 'DE',
                'iso' => 'DE',
                'name' => 'Germany',
                'object' => 'country',
            ],
        ]);
    }

    public function test_misc_get_fr()
    {
        $user = $this->signin();
        $user->locale = 'fr';
        $user->save();

        $response = $this->json('GET', '/api/countries');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => ['*' => $this->jsonCountries],
        ]);
        $response->assertJsonFragment([
            'DEU' => [
                'id' => 'DE',
                'iso' => 'DE',
                'name' => 'Allemagne',
                'object' => 'country',
            ],
        ]);
    }
}
