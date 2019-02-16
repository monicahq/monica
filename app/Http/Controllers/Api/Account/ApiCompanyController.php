<?php

namespace App\Http\Controllers\Api\Account;

use Illuminate\Http\Request;
use App\Models\Account\Company;
use Illuminate\Database\QueryException;
use App\Http\Controllers\Api\ApiController;
use Illuminate\Validation\ValidationException;
use App\Services\Account\Company\CreateCompany;
use App\Services\Account\Company\UpdateCompany;
use App\Services\Account\Company\DestroyCompany;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Http\Resources\Company\Company as CompanyResource;

class ApiCompanyController extends ApiController
{
    /**
     * Get the list of companies.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $companies = auth()->user()->account->companies()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return CompanyResource::collection($companies);
    }

    /**
     * Get the detail of a given company.
     *
     * @param Request $request
     *
     * @return CompanyResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $companyId)
    {
        try {
            $company = Company::where('account_id', auth()->user()->account_id)
                ->where('id', $companyId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new CompanyResource($company);
    }

    /**
     * Store the company.
     *
     * @param Request $request
     *
     * @return CompanyResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $company = app(CreateCompany::class)->execute(
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

        return new CompanyResource($company);
    }

    /**
     * Update a company.
     *
     * @param Request $request
     * @param int $companyId
     *
     * @return CompanyResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $companyId)
    {
        try {
            $company = app(UpdateCompany::class)->execute(
                $request->all()
                    +
                    [
                    'account_id' => auth()->user()->account->id,
                    'company_id' => $companyId,
                ]
            );
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return new CompanyResource($company);
    }

    /**
     * Delete a company.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $companyId)
    {
        try {
            app(DestroyCompany::class)->execute([
                'account_id' => auth()->user()->account->id,
                'company_id' => $companyId,
            ]);
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        } catch (ValidationException $e) {
            return $this->respondValidatorFailed($e->validator);
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return $this->respondObjectDeleted((int) $companyId);
    }
}
