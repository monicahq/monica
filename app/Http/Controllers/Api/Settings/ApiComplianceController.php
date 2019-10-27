<?php

namespace App\Http\Controllers\Api\Settings;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Settings\Compliance\Compliance as ComplianceResource;
use App\Models\Settings\Term;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;

class ApiComplianceController extends ApiController
{
    /**
     * Get the list of terms and privacy policies.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request)
    {
        $terms = Term::orderBy('term_version', 'desc')->paginate($this->getLimitPerPage());

        return ComplianceResource::collection($terms);
    }

    /**
     * Get the detail of a given term.
     *
     * @param Request $request
     *
     * @return ComplianceResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $termId)
    {
        try {
            $term = Term::where('id', $termId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new ComplianceResource($term);
    }
}
