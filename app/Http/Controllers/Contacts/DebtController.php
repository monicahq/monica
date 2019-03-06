<?php

namespace App\Http\Controllers\Contacts;

use App\Models\Contact\Debt;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\DebtRequest;

class DebtController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Contact $contact
     *
     * @return \Illuminate\View\View
     */
    public function index(Contact $contact)
    {
        return view('people.debt.index')
            ->withContact($contact);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Contact $contact
     *
     * @return \Illuminate\View\View
     */
    public function create(Contact $contact)
    {
        return view('people.debt.add')
            ->withContact($contact)
            ->withDebt(new Debt);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param DebtRequest $request
     * @param Contact $contact
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(DebtRequest $request, Contact $contact)
    {
        $contact->debts()->create(
            $request->only([
                'in_debt',
                'amount',
                'reason',
            ])
            + [
                'account_id' => $contact->account_id,
                'status' => 'inprogress',
            ]
        );

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.debt_add_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param Contact $contact
     * @param Debt $debt
     *
     * @return void
     */
    public function show(Contact $contact, Debt $debt): void
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param Debt $debt
     *
     * @return \Illuminate\View\View
     */
    public function edit(Contact $contact, Debt $debt)
    {
        return view('people.debt.edit')
            ->withContact($contact)
            ->withDebt($debt);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param DebtRequest $request
     * @param Contact $contact
     * @param Debt $debt
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(DebtRequest $request, Contact $contact, Debt $debt)
    {
        $debt->update(
            $request->only([
                'in_debt',
                'amount',
                'reason',
            ])
            + [
                'account_id' => $contact->account_id,
                'status' => 'inprogress',
            ]
        );

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.debt_edit_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param Debt $debt
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function destroy(Contact $contact, Debt $debt)
    {
        $debt->delete();

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.debt_delete_success'));
    }
}
