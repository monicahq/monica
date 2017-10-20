<?php

namespace App\Http\Controllers\Api;

use App\Debt;
use Validator;
use App\Contact;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use App\Http\Resources\Debt\Debt as DebtResource;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class ApiDebtController extends ApiController
{
    /**
     * Get the list of debts.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $debts = auth()->user()->account->debts()
                                ->paginate($this->getLimitPerPage());

        return DebtResource::collection($debts);
    }

    /**
     * Get the detail of a given debt
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try {
            $debt = Debt::where('account_id', auth()->user()->account_id)
                ->where('id', $id)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        return new DebtResource($debt);
    }

    /**
     * Store the debt
     * @param  Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'is_for' => 'integer|nullable',
            'name' => 'required|string|max:255',
            'comment' => 'string|max:1000000|nullable',
            'url' => 'string|max:1000000|nullable',
            'value' => 'string|max:255',
            'is_an_idea' => 'boolean',
            'has_been_offered' => 'boolean',
            'date_offered' => 'date|nullable',
            'contact_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
           return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        if (! is_null($request->input('is_for'))) {
            try {
                $contact = Contact::where('account_id', auth()->user()->account_id)
                    ->where('id', $request->input('is_for'))
                    ->firstOrFail();
            } catch (ModelNotFoundException $e) {
                return $this->respondNotFound();
            }
        }

        try {
            $debt = Debt::create($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        $debt->account_id = auth()->user()->account->id;
        $debt->save();

        return new DebtResource($debt);
    }

    /**
     * Update the debt
     * @param  Request $request
     * @param  Integer $debtId
     * @return \Illuminate\Http\Response
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

        // Validates basic fields to create the entry
        $validator = Validator::make($request->all(), [
            'is_for' => 'integer|nullable',
            'name' => 'required|string|max:255',
            'comment' => 'string|max:1000000|nullable',
            'url' => 'string|max:1000000|nullable',
            'value' => 'string|max:255',
            'is_an_idea' => 'boolean',
            'has_been_offered' => 'boolean',
            'date_offered' => 'date|nullable',
            'contact_id' => 'required|integer',
        ]);

        if ($validator->fails()) {
           return $this->setErrorCode(32)
                        ->respondWithError($validator->errors()->all());
        }

        try {
            $contact = Contact::where('account_id', auth()->user()->account_id)
                ->where('id', $request->input('contact_id'))
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->respondNotFound();
        }

        if (! is_null($request->input('is_for'))) {
            try {
                $contact = Contact::where('account_id', auth()->user()->account_id)
                    ->where('id', $request->input('is_for'))
                    ->firstOrFail();
            } catch (ModelNotFoundException $e) {
                return $this->respondNotFound();
            }
        }

        try {
            $debt->update($request->all());
        } catch (QueryException $e) {
            return $this->respondNotTheRightParameters();
        }

        if (is_null($request->input('is_for'))) {
            $debt->is_for = null;
            $debt->save();
        }

        return new DebtResource($debt);
    }

    /**
     * Delete a debt
     * @param  Request $request
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
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
                ->paginate($this->getLimitPerPage());

        return DebtResource::collection($debts);
    }
}
