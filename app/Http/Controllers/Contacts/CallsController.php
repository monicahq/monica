<?php

namespace App\Http\Controllers\Contacts;

use App\Call;
use App\Contact;
use App\Http\Controllers\Controller;
use App\Http\Requests\People\CallsRequest;

class CallsController extends Controller
{
    /**
     * Store a newly created resource in storage.
     *
     * @param CallsRequest $request
     * @param Contact $contact
     * @return \Illuminate\Http\Response
     */
    public function store(CallsRequest $request, Contact $contact)
    {
        $call = $contact->calls()->create(
            $request->only([
                'called_at',
            ])
            + [
                'content' => ($request->get('content') == '' ? null : $request->get('content')),
                'account_id' => $contact->account_id,
            ]
        );

        $contact->logEvent('call', $call->id, 'create');

        $contact->updateLastCalledInfo($call);

        return redirect('/people/'.$contact->hashID())
            ->with('success', trans('people.calls_add_success'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param Contact $contact
     * @param Call $call
     * @return \Illuminate\Http\Response
     */
    public function destroy(Contact $contact, Call $call)
    {
        if ($contact->account_id != $call->account_id) {
            return redirect('/people');
        }

        $call->delete();

        $contact->events()->forObject($call)->get()->each->delete();

        if ($contact->calls()->count() == 0) {
            $contact->last_talked_to = null;
            $contact->save();
        }

        return redirect('/people/'.$contact->hashID())
            ->with('success', trans('people.call_delete_success'));
    }
}
