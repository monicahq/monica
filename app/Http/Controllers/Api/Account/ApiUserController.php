<?php

namespace App\Http\Controllers\Api\Account;

use App\Models\User\User;
use App\Helpers\DateHelper;
use Illuminate\Http\Request;
use App\Models\Settings\Term;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Services\User\AcceptPolicy;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Account\User\User as UserResource;
use App\Http\Resources\Settings\Compliance\Compliance as ComplianceResource;

class ApiUserController extends ApiController
{
    /**
     * Get the detail of the authenticated user.
     *
     * @param  Request  $request
     * @return UserResource
     */
    public function show(Request $request): UserResource
    {
        return new UserResource(auth()->user());
    }

    /**
     * Get the state of a specific term for the user.
     *
     * @param  Request  $request
     * @param  int  $termId
     * @return JsonResponse
     */
    public function get(Request $request, $termId)
    {
        try {
            $term = Term::findOrFail($termId);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $termUser = DB::table('term_user')->where('user_id', auth()->user()->id)
            ->where('account_id', auth()->user()->account_id)
            ->where('term_id', $term->id)
            ->first();

        if ($termUser) {
            $data = [
                'signed' => true,
                'signed_date' => DateHelper::getTimestamp($termUser->created_at),
                'ip_address' => $termUser->ip_address,
                'user' => new UserResource(auth()->user()),
                'term' => new ComplianceResource($term),
            ];
        } else {
            return $this->respondNotFound();
        }

        return $this->respond([
            'data' => $data,
        ]);
    }

    /**
     * Get all the policies ever signed by the authenticated user.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function getSignedPolicies(Request $request)
    {
        $terms = collect();
        $termsForUser = DB::table('term_user')
            ->where('user_id', auth()->user()->id)
            ->get();

        if ($termsForUser->count() == 0) {
            return $this->respondNotFound();
        }

        foreach ($termsForUser as $termUser) {
            $term = Term::findOrFail($termUser->term_id);

            $terms->push([
                'signed' => true,
                'signed_date' => DateHelper::getTimestamp($termUser->created_at),
                'ip_address' => $termUser->ip_address,
                'user' => new UserResource(auth()->user()),
                'term' => new ComplianceResource($term),
            ]);
        }

        return $this->respond([
            'data' => $terms,
        ]);
    }

    /**
     * Sign the latest policy for the authenticated user.
     *
     * @param  Request  $request
     * @return JsonResponse
     */
    public function set(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ip_address' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->respondValidatorFailed($validator);
        }

        try {
            $term = app(AcceptPolicy::class)->execute([
                'account_id' => auth()->user()->account_id,
                'user_id' => auth()->user()->id,
                'ip_address' => $request->input('ip_address'),
            ]);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        try {
            $termUser = DB::table('term_user')->where('user_id', auth()->user()->id)
                ->where('account_id', auth()->user()->account_id)
                ->where('term_id', $term->id)
                ->first();
        } catch (ModelNotFoundException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respond([
            'data' => [
                'signed' => true,
                'signed_date' => DateHelper::getTimestamp($termUser->created_at),
                'ip_address' => $termUser->ip_address,
                'user' => new UserResource(auth()->user()),
                'term' => new ComplianceResource($term),
            ],
        ]);
    }
}
