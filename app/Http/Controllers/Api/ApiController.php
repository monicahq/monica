<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use function Safe\json_decode;
use App\Models\Account\ApiUsage;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;

class ApiController extends Controller
{
    use JsonRespondController;

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
                $this->setSortCriteria($request->input('sort'));

                // It has a sort criteria, but is it a valid one?
                if (empty($this->getSortCriteria())) {
                    return $this->setHTTPStatusCode(400)
                              ->setErrorCode(39)
                              ->respondWithError();
                }
            }

            if ($request->has('limit')) {
                if ($request->input('limit') > config('api.max_limit_per_page')) {
                    return $this->setHTTPStatusCode(400)
                              ->setErrorCode(30)
                              ->respondWithError();
                }

                $this->setLimitPerPage($request->input('limit'));
            }

            if ($request->has('with')) {
                $this->setWithParameter($request->input('with'));
            }

            // make sure the JSON is well formatted if the call sends a JSON
            // if the call contains a JSON, the call must not be a GET or
            // a DELETE
            // TODO: there is probably a much better way to do that
            try {
                if ($request->method() != 'GET' && $request->method() != 'DELETE'
                    && is_null(json_decode($request->getContent()))) {
                    return $this->setHTTPStatusCode(400)
                                ->setErrorCode(37)
                                ->respondWithError();
                }
            } catch (\Safe\Exceptions\JsonException $e) {
                // no error
            }

            return $next($request);
        });
    }

    /**
     * Default request to the API.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function success()
    {
        return $this->respond([
            'success' => [
                'message' => 'Welcome to Monica',
            ],
            'links' => [
                'activities_url' => route('api.activities'),
                'addresses_url' => route('api.addresses'),
                'calls_url' => route('api.calls'),
                'contacts_url' => route('api.contacts'),
                'conversations_url' => route('api.conversations'),
                'countries_url' => route('api.countries'),
                'currencies_url' => route('api.currencies'),
                'documents_url' => route('api.documents'),
                'journal_url' => route('api.journal'),
                'notes_url' => route('api.notes'),
                'relationships_url' => route('api.relationships', ['contact' => ':contactId']),
                'reminders_url' => route('api.reminders'),
                'statistics_url' => route('api.statistics'),
            ],
        ]);
    }

    /**
     * @return string
     */
    public function getWithParameter()
    {
        return $this->withParameter;
    }

    /**
     * @param  string  $with
     * @return self
     */
    public function setWithParameter($with)
    {
        $this->withParameter = $with;

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
     * @param  int  $limit
     * @return self
     */
    public function setLimitPerPage($limit)
    {
        $this->limitPerPage = $limit;

        return $this;
    }

    /**
     * Get the sort direction parameter.
     *
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
     * @param  string  $criteria
     * @return self
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
        ];

        if (in_array($criteria, $acceptedCriteria)) {
            $this->setSQLOrderByQuery($criteria);

            return $this;
        }

        $this->sort = '';

        return $this;
    }

    /**
     * Set both the column and order necessary to perform an orderBy.
     */
    public function setSQLOrderByQuery($criteria)
    {
        $this->sortDirection = $criteria[0] == '-' ? 'desc' : 'asc';
        $this->sort = ltrim($criteria, '-');
    }
}
