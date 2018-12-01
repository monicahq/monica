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



namespace App\Http\Controllers\Contacts;

use App\Models\Contact\Debt;
use App\Helpers\AvatarHelper;
use App\Models\Contact\Contact;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\DebtRequest;

class DebtController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
     */
    public function create(Contact $contact)
    {
        return view('people.debt.add')
            ->withContact($contact)
            ->withAvatar(AvatarHelper::get($contact, 87))
            ->withDebt(new Debt);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param DebtRequest $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
     */
    public function show(Contact $contact, Debt $debt)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param Debt $debt
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact, Debt $debt)
    {
        return view('people.debt.edit')
            ->withContact($contact)
            ->withAvatar(AvatarHelper::get($contact, 87))
            ->withDebt($debt);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param DebtRequest $request
     * @param Contact $contact
     * @param Debt $debt
     * @return \Illuminate\Http\Response
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
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact, Debt $debt)
    {
        $debt->delete();

        return redirect()->route('people.show', $contact)
            ->with('success', trans('people.debt_delete_success'));
    }
}
