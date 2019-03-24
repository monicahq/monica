<?php

namespace App\Http\Controllers\Contacts;

use App\Models\Contact\Call;
use Illuminate\Http\Request;
use App\Models\Contact\Contact;
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
     * @param Contact $contact
     *
     * @return \Illuminate\Http\Resources\Json\AnonymousResourceCollection
     */
    public function index(Request $request, Contact $contact)
    {
        $calls = $contact->calls()->get();

        return CallResource::collection($calls);
    }

    /**
     * Store a call.
     *
     * @param  Contact $contact
     * @return Call
     */
    public function store(Request $request, Contact $contact)
    {
        return app(CreateCall::class)->execute([
            'account_id' => auth()->user()->account->id,
            'contact_id' => $contact->id,
            'content' => $request->get('content'),
            'called_at' => $request->get('called_at'),
            'contact_called' => $request->get('contact_called'),
            'emotions' => $request->get('emotions'),
        ]);
    }

    /**
     * Update a call.
     *
     * @param  Contact $contact
     * @param  Call $call
     * @return Call
     */
    public function update(Request $request, Contact $contact, Call $call)
    {
        return app(UpdateCall::class)->execute([
            'account_id' => auth()->user()->account->id,
            'call_id' => $call->id,
            'content' => $request->get('content'),
            'called_at' => $request->get('called_at'),
            'contact_called' => $request->get('contact_called'),
            'emotions' => $request->get('emotions'),
        ]);
    }

    /**
     * Delete the call.
     *
     * @param Request $request
     * @param Contact $contact
     * @param Call $call
     *
     * @return null|\Illuminate\Http\JsonResponse
     */
    public function destroy(Request $request, Contact $contact, Call $call)
    {
        $data = [
            'account_id' => auth()->user()->account->id,
            'call_id' => $call->id,
        ];

        try {
            if (app(DestroyCall::class)->execute($data)) {
                return $this->respondObjectDeleted($call->id);
            }
        } catch (\Exception $e) {
            return $this->respondNotFound();
        }
    }
}
