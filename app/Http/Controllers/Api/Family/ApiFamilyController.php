<?php

namespace App\Http\Controllers\Api\Family;

use Illuminate\Http\Request;
use App\Models\Family\Family;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use App\Services\Family\Family\CreateFamily;
use App\Services\Family\Family\UpdateFamily;
use App\Services\Family\Family\DestroyFamily;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Family\Family as FamilyResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiFamilyController extends ApiController
{
    /**
     * Get the list of families.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $families = auth()->user()->account->families()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return FamilyResource::collection($families);
    }

    /**
     * Get the detail of a given family.
     *
     * @param Request $request
     *
     * @return FamilyResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $familyId)
    {
        try {
            $family = Family::where('account_id', auth()->user()->account_id)
                ->where('id', $familyId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new FamilyResource($family);
    }

    /**
     * Store the family.
     *
     * @param Request $request
     *
     * @return FamilyResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $family = app(CreateFamily::class)->execute(
                $request->except(['account_id'])
                    +
                    [
                        'account_id' => auth()->user()->account->id,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new FamilyResource($family);
    }

    /**
     * Update the family.
     *
     * @param Request $request
     * @param int $familyId
     *
     * @return FamilyResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $familyId)
    {
        try {
            $family = app(UpdateFamily::class)->execute(
                $request->except(['account_id', 'family_id'])
                    +
                    [
                        'account_id' => auth()->user()->account->id,
                        'family_id' => $familyId,
                    ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new FamilyResource($family);
    }

    /**
     * Delete a family.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $familyId)
    {
        try {
            app(DestroyFamily::class)->execute([
                'account_id' => auth()->user()->account->id,
                'family_id' => $familyId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respondObjectDeleted((int) $familyId);
    }
}
