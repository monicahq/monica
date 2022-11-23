<?php

namespace App\Http\Controllers;

use App\Traits\JsonRespondController;

class ApiController extends Controller
{
    use JsonRespondController;

    protected int $limitPerPage = 10;

    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if ($request->has('limit')) {
                if ($request->input('limit') > config('api.max_limit_per_page')) {
                    return $this->setHTTPStatusCode(400)
                        ->setErrorCode(30)
                        ->respondWithError();
                }

                $this->setLimitPerPage($request->input('limit'));
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
}
