<?php

namespace App\Http\Controllers\Api\Contact;

use App\Http\Controllers\Api\ApiController;
use App\Http\Resources\Occupation\Occupation as OccupationResource;
use App\Models\Contact\Occupation;
use App\Services\Contact\Occupation\CreateOccupation;
use App\Services\Contact\Occupation\DestroyOccupation;
use App\Services\Contact\Occupation\UpdateOccupation;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ApiOccupationController extends ApiController
{
    /**
     * Get the list of occupations.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $occupations = auth()->user()->account->occupations()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return OccupationResource::collection($occupations);
    }

    /**
     * Get the detail of a given occupation.
     *
     * @param Request $request
     *
     * @return OccupationResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $occupationId)
    {
        try {
            $occupation = Occupation::where('account_id', auth()->user()->account_id)
                ->where('id', $occupationId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new OccupationResource($occupation);
    }

    /**
     * Store the occupation.
     *
     * @param Request $request
     *
     * @return OccupationResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $occupation = app(CreateOccupation::class)->execute(
                $request->except(['account_id'])
                    +
                    [
                        'account_id' => auth()->user()->account_id,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new OccupationResource($occupation);
    }

    /**
     * Update an occupation.
     *
     * @param Request $request
     * @param int $occupationId
     *
     * @return OccupationResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $occupationId)
    {
        try {
            $occupation = app(UpdateOccupation::class)->execute(
                $request->except(['account_id', 'occupation_id'])
                    +
                    [
                        'account_id' => auth()->user()->account_id,
                        'occupation_id' => $occupationId,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new OccupationResource($occupation);
    }

    /**
     * Delete an occupation.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $occupationId)
    {
        try {
            app(DestroyOccupation::class)->execute([
                'account_id' => auth()->user()->account_id,
                'occupation_id' => $occupationId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respondObjectDeleted((int) $occupationId);
    }
}
