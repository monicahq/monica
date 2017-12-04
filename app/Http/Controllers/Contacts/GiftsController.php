<?php

namespace App\Http\Controllers\Contacts;

use App\Gift;
use App\Contact;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\GiftsRequest;

class GiftsController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function index(Contact $contact)
    {
        return view('people.gifts.index')
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
                'is_an_idea' => ! $request->get('offered'),
                'has_been_offered' => $request->get('offered'),
            ]
        );

        if ($request->get('has_recipient')) {
            $gift->forRecipient($request->get('recipient'))->save();
        }

        $contact->logEvent('gift', $gift->id, 'create');

        return redirect('/people/'.$contact->id)
            ->with('success', trans('people.gifts_add_success'));
    }

    /**
     * Display the specified resource.
     *
     * @param Contact $contact
     * @param Gift $gift
     * @return \Illuminate\Http\Response
     */
    public function show(Contact $contact, Gift $gift)
    {
        //
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
