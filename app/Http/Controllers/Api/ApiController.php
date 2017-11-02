<?php

namespace App\Http\Controllers\Api;

use App\ApiUsage;
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
     * @var int
     */
    protected $limitPerPage = 10;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            $apiUsage = (new ApiUsage)->log($request);

            if ($request->has('limit')) {
                if ($request->get('limit') > config('api.max_limit_per_page')) {
                    return $this->setHTTPStatusCode(400)
                              ->setErrorCode(30)
                              ->respondWithError(config('api.error_codes.30'));
                }

                $this->setLimitPerPage($request->get('limit'));
            }

            // make sure the JSON is well formatted if the given call sends a JSON
            // TODO: there is probably a much better way to do that
            if ($request->method() != 'GET' and $request->method() != 'DELETE') {
                if (is_null(json_decode($request->getContent()))) {
                    return $this->setHTTPStatusCode(400)
                              ->setErrorCode(37)
                              ->respondWithError(config('api.error_codes.37'));
                }
            }

            return $next($request);
        });
    }

    /**
     * Default request to the API.
     * @return json
     */
    public function success()
    {
        return $this->respond([
            'success' => [
                'message' => 'Welcome to Monica',
            ],
        ]);
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
     * @param  array $data
     * @param  array  $headers [description]
     * @return Response
     */
    public function respond($data, $headers = [])
    {
        return response()->json($data, $this->getHTTPStatusCode(), $headers);
    }

    /**
     * Sends a response not found (404) to the request.
     * @param string $message
     */
    public function respondNotFound($message = 'Not found!')
    {
        return $this->setHTTPStatusCode(404)
                    ->setErrorCode(31)
                    ->respondWithError($message);
    }

    /**
     * Sends an error when the query didn't have the right parameters for
     * creating an object.
     * @param string $message
     */
    public function respondNotTheRightParameters($message = 'Too many parameters')
    {
        return $this->setHTTPStatusCode(500)
                    ->setErrorCode(33)
                    ->respondWithError($message);
    }

    /**
     * Sends a response with error.
     * @param string message
     */
    public function respondWithError($message)
    {
        return $this->respond([
            'error' => [
                'message' => $message,
                'error_code' => $this->getErrorCode(),
            ],
        ]);
    }

    /**
     * Sends a response that the object has been deleted, and also indicates
     * the id of the object that has been deleted.
     * @param  int $id
     */
    public function respondObjectDeleted($id)
    {
        return $this->respond([
            'deleted' => true,
            'id' => $id,
        ]);
    }
}
