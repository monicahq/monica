<?php

namespace App\Http\Controllers\Api\Account;

use Illuminate\Http\Request;
use App\Models\Contact\Gender;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use App\Services\Account\Gender\CreateGender;
use App\Services\Account\Gender\UpdateGender;
use App\Services\Account\Gender\DestroyGender;
use Illuminate\Validation\ValidationException;
use App\Http\Resources\Gender\Gender as GenderResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiGenderController extends ApiController
{
    /**
     * Get the list of genders.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $genders = auth()->user()->account->genders()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return GenderResource::collection($genders);
    }

    /**
     * Get the detail of a given gender.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $genderId)
    {
        try {
            $gender = Gender::where('account_id', auth()->user()->account_id)
                ->where('id', $genderId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new GenderResource($gender);
    }

    /**
     * Store the gender.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $gender = (new CreateGender)->execute(
                $request->all()
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

        return new GenderResource($gender);
    }

    /**
     * Update a gender.
     *
     * @param  Request $request
     * @param  int $genderId
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $genderId)
    {
        try {
            $gender = (new UpdateGender)->execute(
                $request->all()
                    +
                    [
                    'account_id' => auth()->user()->account->id,
                    'gender_id' => $genderId,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new GenderResource($gender);
    }

    /**
     * Delete a gender.
     *
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $genderId)
    {
        try {
            (new DestroyGender)->execute([
                'account_id' => auth()->user()->account->id,
                'gender_id' => $genderId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respondObjectDeleted((int) $genderId);
    }
}
