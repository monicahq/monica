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


namespace Tests;

use Tests\Traits\ApiSignIn;
use Illuminate\Foundation\Testing\TestResponse;

class ApiTestCase extends TestCase
{
    use ApiSignIn;

    /**
     * Test that the response contains a not found notification.
     *
     * @param TestResponse $response
     */
    public function expectNotFound(TestResponse $response)
    {
        $response->assertStatus(404);

        $response->assertJson([
            'error' => [
                'message' => 'The resource has not been found',
                'error_code' => 31,
            ],
        ]);
    }

    /**
     * Test that the response contains a not found notification.
     *
     * @param TestResponse $response
     * @param string|array $message
     */
    public function expectDataError(TestResponse $response, $message = '')
    {
        $response->assertStatus(400);

        $response->assertJson([
            'error' => [
                'message' => $message,
                'error_code' => 32,
            ],
        ]);
    }

    /**
     * Test that the response contains a not found notification.
     *
     * @param TestResponse $response
     * @param string|array $message
     */
    public function expectInvalidParameter(TestResponse $response, $message = '')
    {
        $response->assertStatus(400);

        $response->assertJson([
            'error' => [
                'message' => $message,
                'error_code' => 41,
            ],
        ]);
    }
}
