<?php

namespace App\Http\Controllers\Contacts;

use App\Helpers\DateHelper;
use App\Models\Contact\Call;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
use Illuminate\Http\JsonResponse;
use App\Http\Controllers\Controller;
use App\Traits\JsonRespondController;
use App\Services\Contact\Call\CreateCall;
use App\Services\Contact\Call\UpdateCall;
use App\Services\Contact\Call\DestroyCall;
use App\Http\Resources\Call\Call as CallResource;

class CallsController extends Controller
{
    use JsonRespondController;

    /**
     * Display the list of calls.
     *
     * @param  Contact  $contact
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request, Contact $contact)
    {
        $calls = $contact->calls()->get();

        return CallResource::collection($calls);
    }

    /**
     * Display the timestamp of the last phone contact.
     *
     * @param  Contact  $contact
     * @return JsonResponse
     */
    public function lastCalled(Contact $contact): JsonResponse
    {
        $lastTalkedTo = $contact->last_talked_to;

        if ($lastTalkedTo !== null) {
            $lastTalkedTo = DateHelper::getShortDate($contact->last_talked_to);
        }

        return $this->respond([
            'last_talked_to' => $lastTalkedTo,
        ]);
    }

    /**
     * Store a call.
     *
     * @param  Contact  $contact
     * @return Call
     */
    public function store(Request $request, Contact $contact)
    {
        return app(CreateCall::class)->execute([
            'account_id' => auth()->user()->account_id,
            'contact_id' => $contact->id,
            'content' => $request->input('content'),
            'called_at' => $request->input('called_at'),
            'contact_called' => $request->input('contact_called'),
            'emotions' => $request->input('emotions'),
        ]);
    }

    /**
     * Update a call.
     *
     * @param  Contact  $contact
     * @param  Call  $call
     * @return Call
     */
    public function update(Request $request, Contact $contact, Call $call)
    {
        return app(UpdateCall::class)->execute([
            'account_id' => auth()->user()->account_id,
            'call_id' => $call->id,
            'content' => $request->input('content'),
            'called_at' => $request->input('called_at'),
            'contact_called' => $request->input('contact_called'),
            'emotions' => $request->input('emotions'),
        ]);
    }

    /**
     * Delete the call.
     *
     * @param  Request  $request
     * @param  Contact  $contact
     * @param  Call  $call
     * @return null|\Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Contact $contact, Call $call): ?JsonResponse
    {
        $data = [
            'account_id' => auth()->user()->account_id,
            'call_id' => $call->id,
        ];

        if (app(DestroyCall::class)->execute($data)) {
            return $this->respondObjectDeleted($call->id);
        }

        return null;
    }
}
