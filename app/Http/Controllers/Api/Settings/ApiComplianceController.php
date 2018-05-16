<?php

namespace App\Http\Controllers\Api\Settings;

use Illuminate\Http\Request;
use App\Models\Settings\Term;
use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Compliance\Compliance as ComplianceResource;

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
}
