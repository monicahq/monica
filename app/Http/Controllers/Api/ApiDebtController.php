<?php

namespace App\Http\Controllers\Api;

use App\Models\Contact\Debt;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use Illuminate\Validation\Rule;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;
use App\Http\Resources\Debt\Debt as DebtResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiDebtController extends ApiController
{
    /**
     * Get the list of debts.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $debts = auth()->user()->account->debts()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());
        } catch (QueryException $e) {
            return $this->respondInvalidQuery();
        }

        return DebtResource::collection($debts);
    }

    /**
     * Get the detail of a given debt.
     *
     * @param Request $request
     *
     * @return DebtResource|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $debtId)
    {
        try {
            $debt = Debt::where('account_id', auth()->user()->account_id)
                ->where('id', $debtId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new DebtResource($debt);
    }

    /**
     * Store the debt.
     *
     * @param Request $request
     *
     * @return DebtResource|\Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $debt = Debt::create(
                $request->all()
                + ['account_id' => auth()->user()->account_id]
            );
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new DebtResource($debt);
    }

    /**
     * Update the debt.
     *
     * @param Request $request
     * @param int $debtId
     *
     * @return DebtResource|\Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $debtId)
    {
        try {
            $debt = Debt::where('account_id', auth()->user()->account_id)
                ->where('id', $debtId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $isvalid = $this->validateUpdate($request);
        if ($isvalid !== true) {
            return $isvalid;
        }

        try {
            $debt->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        return new DebtResource($debt);
    }

    /**
     * Validate the request for update.
     *
     * @param  Request $request
     * @return \Illuminate\Http\JsonResponse|true
     */
    private function validateUpdate(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'in_debt' => [
                'required',
                'string',
                Rule::in(['yes', 'no']),
            ],
            'status' => [
                'required',
                'string',
                Rule::in(['inprogress', 'completed']),
            ],
            'amount' => 'required|numeric',
            'reason' => 'string|max:1000000|nullable',
            'contact_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
            return $this->respondValidatorFailed($validator);
        }

        try {
            Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return true;
    }

    /**
     * Delete a debt.
     *
     * @param Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, $debtId)
    {
        try {
            $debt = Debt::where('account_id', auth()->user()->account_id)
                ->where('id', $debtId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $debt->delete();

        return $this->respondObjectDeleted($debt->id);
    }

    /**
     * Get the list of debts for the given contact.
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection|\Illuminate\Http\JsonResponse
     */
    public function debts(Request $request, $contactId)
    {
        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $contactId)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        $debts = $contact->debts()
                ->orderBy($this->sort, $this->sortDirection)
                ->paginate($this->getLimitPerPage());

        return DebtResource::collection($debts);
    }
}
