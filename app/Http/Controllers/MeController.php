<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use App\Services\Contact\Contact\DeleteMeContact;
use App\Services\Contact\Contact\SetMeContact;
use App\Traits\JsonRespondController;

class MeController extends Controller
{
    use JsonRespondController;

    /**
     * Set a contact as 'me'.
     *
     * @param  Request  $request
     * @param  int  $contactId
     * @return string
     */
    public function store(Request $request, int $contactId)
    {
        app(SetMeContact::class)->execute([
            'contact_id' => $contactId,
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
