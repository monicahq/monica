<?php

namespace App\Http\Controllers\People;

use App\Contact;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\IntroductionsRequest;

class IntroductionsController extends Controller
{
    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param Gift $gift
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact)
    {
        return view('people.dashboard.introductions.edit')
            ->withContact($contact);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param GiftsRequest $request
     * @param Contact $contact
     * @param Gift $gift
     * @return \Illuminate\Http\Response
     */
    public function update(GiftsRequest $request, Contact $contact, Gift $gift)
    {
        $gift->update(
            $request->only([
                'name',
                'comment',
                'url',
                'value',
            ])
            + [
                'account_id' => $contact->account_id,
                'is_an_idea' => ! (bool) $request->get('offered'),
                'has_been_offered' => (bool) $request->get('offered'),
            ]
        );

        if ($request->get('has_recipient')) {
            $gift->forRecipient($request->get('recipient'))->save();
        }

        $contact->logEvent('gift', $gift->id, 'update');

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.gifts_update_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param Gift $gift
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact, Gift $gift)
    {
        $gift->delete();

        $contact->events()->forObject($gift)->get()->each->delete();

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.gifts_delete_success'));
    }
}
