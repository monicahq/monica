<?php

namespace App\Http\Controllers\Contacts;

use App\Gift;
use App\Contact;
use App\Helpers\MoneyHelper;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\GiftsRequest;

class GiftsController extends Controller
{
    /**
     * List all the gifts for the given contact.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function get(Contact $contact)
    {
        $giftsCollection = collect([]);
        $gifts = $contact->gifts()->get();

        foreach ($gifts as $gift) {
            $data = [
                'id' => $gift->id,
                'name' => $gift->name,
                'is_for' => $gift->recipient_name,
                'comment' => $gift->comment,
                'url' => $gift->url,
                'value' => MoneyHelper::format($gift->value),
                'does_value_exist' => (bool) $gift->value,
                'is_an_idea' => $gift->is_an_idea,
                'has_been_offered' => $gift->has_been_offered,
                'has_been_received' => $gift->has_been_received,
                'offered_at' => \App\Helpers\DateHelper::getShortDate($gift->offered_at),
                'received_at' => \App\Helpers\DateHelper::getShortDate($gift->received_at),
                'created_at' => \App\Helpers\DateHelper::getShortDate($gift->created_at),
                'edit' => false,
                'show_comment' => false,
            ];
            $giftsCollection->push($data);
        }

        return $giftsCollection;
    }

    /**
     * Mark a gift as being offered.
     * @param  Contact $contact
     * @param  Gift    $gift
     * @return void
     */
    public function toggle(Contact $contact, Gift $gift)
    {
        $gift->toggle();

        return $gift;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function create(Contact $contact)
    {
        return view('people.gifts.add')
            ->withContact($contact)
            ->withGift(new Gift);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param GiftsRequest $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function store(GiftsRequest $request, Contact $contact)
    {
        $gift = $contact->gifts()->create(
            $request->only([
                'name',
                'comment',
                'url',
                'value',
            ])
            + [
                'account_id' => $contact->account_id,
                'is_an_idea' => ($request->get('offered') == 'idea' ? 1 : 0),
                'has_been_offered' => ($request->get('offered') == 'offered' ? 1 : 0),
                'has_been_received' => ($request->get('offered') == 'received' ? 1 : 0),
            ]
        );

        if ($request->get('has_recipient')) {
            $gift->is_for = $request->get('recipient');
            $gift->save();
        }

        $contact->logEvent('gift', $gift->id, 'create');

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.gifts_add_success'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param Contact $contact
     * @param Gift $gift
     * @return \Illuminate\Http\Response
     */
    public function edit(Contact $contact, Gift $gift)
    {
        return view('people.gifts.edit')
            ->withContact($contact)
            ->withGift($gift);
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
            $gift->is_for = $request->get('recipient');
            $gift->save();
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
    }
}
