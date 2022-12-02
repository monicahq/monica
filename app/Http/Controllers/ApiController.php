<?php

namespace App\Http\Controllers;

use App\Traits\JsonRespondController;
use Closure;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApiController extends Controller
{
    use JsonRespondController;

    protected int $limitPerPage = 10;

    public function __construct()
    {
        $this->middleware(function (Request $request, Closure $next) {
            if ($request->has('limit')) {
                if ($request->input('limit') > config('api.max_limit_per_page')) {
                    return $this->setHTTPStatusCode(400)
                        ->setErrorCode(30)
                        ->respondWithError();
                }

                $this->setLimitPerPage($request->integer('limit'));
            }

            return $next($request);
        });
    }

    public function getLimitPerPage(): int
    {
        return $this->limitPerPage;
    }

    public function setLimitPerPage(int $limit): static
    {
        $this->limitPerPage = $limit;

        return $this;
    }

    /**
     * Execute an action on the controller.
     *
     * @param  string  $method
     * @param  array  $parameters
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function callAction($method, $parameters)
    {
        try {
            return $this->{$method}(...array_values($parameters));
        } catch (ModelNotFoundException) {
            return $this->respondNotFound();
        } catch (QueryException) {
            return $this->respondInvalidQuery();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        }
    }
}
