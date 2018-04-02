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
    protected $limitPerPage = 0;

    /**
     * @var string
     */
    protected $sort = 'created_at';

    /**
     * @var string
     */
    protected $withParameter = null;

    /**
     * @var string
     */
    protected $sortDirection = 'asc';

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            (new ApiUsage)->log($request);

            if ($request->has('sort')) {
                $this->setSortCriteria($request->get('sort'));

                // It has a sort criteria, but is it a valid one?
                if (is_null($this->getSortCriteria())) {
                    return $this->setHTTPStatusCode(400)
                              ->setErrorCode(39)
                              ->respondWithError(config('api.error_codes.39'));
                }
            }

            if ($request->has('limit')) {
                if ($request->get('limit') > config('api.max_limit_per_page')) {
                    return $this->setHTTPStatusCode(400)
                              ->setErrorCode(30)
                              ->respondWithError(config('api.error_codes.30'));
                }

                $this->setLimitPerPage($request->get('limit'));
            }

            if ($request->has('with')) {
                $this->setWithParameter($request->get('with'));
            }

            // make sure the JSON is well formatted if the given call sends a JSON
            // TODO: there is probably a much better way to do that
            if ($request->method() != 'GET' && $request->method() != 'DELETE'
                && is_null(json_decode($request->getContent()))) {
                return $this->setHTTPStatusCode(400)
                            ->setErrorCode(37)
                            ->respondWithError(config('api.error_codes.37'));
            }

            return $next($request);
        });
    }

    /**
     * Default request to the API.
     * @return \Illuminate\Http\JsonResponse
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
     * @return string
     */
    public function getWithParameter()
    {
        return $this->withParameter;
    }

    /**
     * @param string $with
     * @return $this
     */
    public function setWithParameter($with)
    {
        $this->withParameter = $with;

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
     * Get the sort direction parameter.
     * @return string
     */
    public function getSortDirection()
    {
        return $this->sortDirection;
    }

    /**
     * @return string
     */
    public function getSortCriteria()
    {
        return $this->sort;
    }

    /**
     * @param string $criteria
     * @return $this
     */
    public function setSortCriteria($criteria)
    {
        $acceptedCriteria = [
            'created_at',
            'updated_at',
            '-created_at',
            '-updated_at',
            'completed_at',
            '-completed_at',
            'called_at',
            '-called_at',
            'favorited_at',
            '-favorited_at',
            'next_expected_date',
            '-next_expected_date',
        ];

        if (in_array($criteria, $acceptedCriteria)) {
            $this->setSQLOrderByQuery($criteria);

            return $this;
        }

        $this->sort = null;

        return $this;
    }

    /**
     * Set both the column and order necessary to perform an orderBy.
     */
    public function setSQLOrderByQuery($criteria)
    {
        $this->sortDirection = 'asc';
        $this->sort = $criteria;

        $firstCharacter = $this->getSortCriteria()[0];

        if ($firstCharacter == '-') {
            $this->sort = substr($this->getSortCriteria(), 1);
            $this->sortDirection = 'desc';
        }
    }

    /**
     * Sends a JSON to the consumer.
     * @param  array $data
     * @param  array  $headers [description]
     * @return \Illuminate\Http\JsonResponse
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
     * Sends a response invalid query to the request.
     * @param string $message
     */
    public function respondInvalidQuery($message = 'Invalid query')
    {
        return $this->setHTTPStatusCode(500)
            ->setErrorCode(40)
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
