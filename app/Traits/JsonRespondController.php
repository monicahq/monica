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


namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\Validation\Validator;

trait JsonRespondController
{
    /**
     * @var int
     */
    protected $httpStatusCode = 200;

    /**
     * @var int
     */
    protected $errorCode;

    /**
     * Get HTTP status code of the response.
     *
     * @return int
     */
    public function getHTTPStatusCode()
    {
        return $this->httpStatusCode;
    }

    /**
     * Set HTTP status code of the response.
     *
     * @param int $statusCode
     * @return $this
     */
    public function setHTTPStatusCode($statusCode)
    {
        $this->httpStatusCode = $statusCode;

        return $this;
    }

    /**
     * Get error code of the response.
     *
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * Set error code of the response.
     *
     * @param int $errorCode
     * @return $this
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;

        return $this;
    }

    /**
     * Sends a JSON to the consumer.
     *
     * @param  array $data
     * @param  array  $headers [description]
     * @return JsonResponse
     */
    public function respond($data, $headers = [])
    {
        return response()->json($data, $this->getHTTPStatusCode(), $headers);
    }

    /**
     * Sends a response unauthorized (401) to the request.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function respondUnauthorized($message = null)
    {
        return $this->setHTTPStatusCode(401)
                    ->setErrorCode(42)
                    ->respondWithError($message);
    }

    /**
     * Sends a response not found (404) to the request.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function respondNotFound()
    {
        return $this->setHTTPStatusCode(404)
                    ->setErrorCode(31)
                    ->respondWithError();
    }

    /**
     * Sends a response invalid query (http 500) to the request.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function respondInvalidQuery($message = null)
    {
        return $this->setHTTPStatusCode(500)
                    ->setErrorCode(40)
                    ->respondWithError($message);
    }

    /**
     * Sends an error when the query didn't have the right parameters for
     * creating an object.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function respondNotTheRightParameters($message = null)
    {
        return $this->setHTTPStatusCode(500)
                    ->setErrorCode(33)
                    ->respondWithError($message);
    }

    /**
     * Sends an error when the query contains invalid parameters.
     *
     * @param string $message
     * @return JsonResponse
     */
    public function respondInvalidParameters($message = null)
    {
        return $this->setHTTPStatusCode(400)
                    ->setErrorCode(41)
                    ->respondWithError($message);
    }

    /**
     * Sends an error when the validator failed.
     *
     * @param Validator $validator
     * @return JsonResponse
     */
    public function respondValidatorFailed(Validator $validator)
    {
        return $this->setHTTPStatusCode(400)
                    ->setErrorCode(32)
                    ->respondWithError($validator->errors()->all());
    }

    /**
     * Sends a response with error.
     *
     * @param string message
     * @return JsonResponse
     */
    public function respondWithError($message = null)
    {
        return $this->respond([
            'error' => [
                'message' => $message ?? config('api.error_codes.'.$this->getErrorCode()),
                'error_code' => $this->getErrorCode(),
            ],
        ]);
    }

    /**
     * Sends a response that the object has been deleted, and also indicates
     * the id of the object that has been deleted.
     *
     * @param  int $id
     * @return JsonResponse
     */
    public function respondObjectDeleted($id)
    {
        return $this->respond([
            'deleted' => true,
            'id' => $id,
        ]);
    }
}
