<?php

/**
 *  This file is part of Monica.
 *
 *  Monica is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as published by
 *  the Free Software Foundation, either version 3 of the License, or
 *  (at your option) any later version.
 *
 *  Monica is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
 **/

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
