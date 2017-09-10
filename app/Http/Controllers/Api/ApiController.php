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
    protected $limitPerPage = 10;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
          if ($request->has('limit')) {
              if ($request->get('limit') > config('api.max_limit_per_page')) {
                  return $this->setHTTPStatusCode(400)
                              ->setErrorCode(30)
                              ->respondWithError(config('api.error_codes.30'));
                }

                $this->setLimitPerPage($request->get('limit'));
            }

            return $next($request);
        });
    }

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
    public function getLimitPerPage()
    {
        return $this->limitPerPage;
    }

    /**
     * @param int $limit
     * @return $this
     */
    public function setLimitPerPage($limit)
    {
        $this->limitPerPage = $limit;
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
                    ->setErrorCode(31)
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
                'error_code' => $this->getErrorCode(),
            ]
        ]);
    }
}
