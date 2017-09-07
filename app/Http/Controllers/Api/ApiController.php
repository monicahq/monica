<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class ApiController extends Controller
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
     * @var integer
     */
    protected $limit = 10;

    /**
     * @return int
     */
    public function getHTTPStatusCode()
    {
        return $this->httpStatusCode;
    }

    /**
     * @param int $statusCode
     * @return $this
     */
    public function setHTTPStatusCode($statusCode)
    {
        $this->httpStatusCode = $statusCode;
        return $this;
    }

    /**
     * @return int
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param int $errorCode
     * @return $this
     */
    public function setErrorCode($errorCode)
    {
        $this->errorCode = $errorCode;
        return $this;
    }

    /**
     * @return int
     */
    public function getLimit()
    {
        return $this->limit;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function setLimit($limit)
    {
        $this->limit = $limit;
        return $this;
    }

    /**
     * Sends a JSON to the consumer.
     * @param  Array $data
     * @param  array  $headers [description]
     * @return Response
     */
    public function respond($data, $headers = [])
    {
        return response()->json($data, $this->getHTTPStatusCode(), $headers);
    }

    /**
     * Sends a response not found (404) to the request
     * @param string $message
     */
    public function respondNotFound($message = "Not found!")
    {
        return $this->setHTTPStatusCode(404)
                    ->respondWithError($message);
    }

    /**
    * Sends a response with error
    * @param string message
    */
    public function respondWithError($message)
    {
        return $this->respond([
            'error' => [
                'message' => $message,
                'status_code' => $this->getErrorCode(),
            ]
        ]);
    }

    /**
    * Checks if a limit is set in the query. If so, check if the limit is not
    * out of the range accepted by the API. If not, sets the limit.
    */
    protected function checkLimit(Request $request)
    {
        if ($request->has('limit')) {
            if ($request->get('limit') > 100) {
                return $this->setHTTPStatusCode(400)
                            ->setErrorCode(30)
                            ->respondWithError(config('api.error_codes.30'));
            }

            $this->setLimit($request->get('limit'));
        }
    }
}
