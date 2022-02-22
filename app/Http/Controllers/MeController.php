<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Traits\JsonRespondController;
use App\Services\Contact\Contact\SetMeContact;
use App\Services\Contact\Contact\DeleteMeContact;

class MeController extends Controller
{
    use JsonRespondController;

    /**
     * Set a contact as 'me'.
     *
     * @param  Request  $request
     * @return string
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'contact_id' => 'required|integer|exists:contacts,id',
        ]);

        app(SetMeContact::class)->execute([
            'contact_id' => $request->input('contact_id'),
            'account_id' => $request->user()->account_id,
            'user_id' => $request->user()->id,
        ]);

        return $this->respond(['true']);
    }

    /**
     * Removes contact as 'me' association.
     *
     * @param  Request  $request
     * @return string
     */
    public function destroy(Request $request)
    {
        app(DeleteMeContact::class)->execute([
            'account_id' => $request->user()->account_id,
            'user_id' => $request->user()->id,
        ]);

        return $this->respond(['true']);
    }
}
