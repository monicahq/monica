<?php

namespace App\Http\Controllers\Api\Settings;

use Illuminate\Http\Request;
use App\Models\Settings\Term;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Settings\Compliance\Compliance as ComplianceResource;

class ApiComplianceController extends ApiController
{
    /**
     * Get the list of terms and privacy policies.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $terms = Term::orderBy('term_version', 'desc')->paginate($this->getLimitPerPage());

        return ComplianceResource::collection($terms);
    }

    /**
     * Get the detail of a given term.
     * @param  Request $request
     * @return \Illuminate\Http\Response
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
