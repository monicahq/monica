<?php

namespace App\Domains\Contact\ManageCalls\Web\Controllers;

use App\Domains\Contact\ManageCalls\Services\CreateCall;
use App\Domains\Contact\ManageCalls\Services\DestroyCall;
use App\Domains\Contact\ManageCalls\Services\UpdateCall;
use App\Domains\Contact\ManageCalls\Web\ViewHelpers\ModuleCallsViewHelper;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContactModuleCallController extends Controller
{
    public function store(Request $request, string $vaultId, string $contactId)
    {
        $carbonDate = Carbon::parse($request->input('called_at'));
        $answered = false;
        $whoInitiated = 'me';

        switch ($request->input('who_initiated')) {
            case 'contact_not_answered':
                $whoInitiated = 'contact';
                $answered = false;
                break;

            case 'me_not_answered':
                $whoInitiated = 'me';
                $answered = false;
                break;

            case 'contact':
                $whoInitiated = 'contact';
                $answered = true;
                break;

            case 'me':
                $whoInitiated = 'me';
                $answered = true;
                break;
        }

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'call_reason_id' => $request->input('call_reason_id') == 0 ? null : $request->input('call_reason_id'),
            'emotion_id' => $request->input('emotion_id') == 0 ? null : $request->input('emotion_id'),
            'called_at' => $carbonDate->format('Y-m-d'),
            'duration' => $request->input('duration'),
            'description' => $request->input('description'),
            'type' => $request->input('type'),
            'answered' => $answered,
            'who_initiated' => $whoInitiated,
        ];

        $call = (new CreateCall)->execute($data);
        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleCallsViewHelper::dto($contact, $call, Auth::user()),
        ], 201);
    }

    public function update(Request $request, string $vaultId, string $contactId, int $callId)
    {
        $carbonDate = Carbon::parse($request->input('called_at'));
        $answered = false;
        $whoInitiated = 'me';

        switch ($request->input('who_initiated')) {
            case 'contact_not_answered':
                $whoInitiated = 'contact';
                $answered = false;
                break;

            case 'me_not_answered':
                $whoInitiated = 'me';
                $answered = false;
                break;

            case 'contact':
                $whoInitiated = 'contact';
                $answered = true;
                break;

            case 'me':
                $whoInitiated = 'me';
                $answered = true;
                break;

            default:
                break;
        }

        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'call_id' => $callId,
            'call_reason_id' => $request->input('call_reason_id') == 0 ? null : $request->input('call_reason_id'),
            'emotion_id' => $request->input('emotion_id') == 0 ? null : $request->input('emotion_id'),
            'called_at' => $carbonDate->format('Y-m-d'),
            'duration' => $request->input('duration'),
            'description' => $request->input('description'),
            'type' => $request->input('type'),
            'answered' => $answered,
            'who_initiated' => $whoInitiated,
        ];

        $call = (new UpdateCall)->execute($data);
        $contact = Contact::find($contactId);

        return response()->json([
            'data' => ModuleCallsViewHelper::dto($contact, $call, Auth::user()),
        ], 200);
    }

    public function destroy(Request $request, string $vaultId, string $contactId, int $callId)
    {
        $data = [
            'account_id' => Auth::user()->account_id,
            'author_id' => Auth::id(),
            'vault_id' => $vaultId,
            'contact_id' => $contactId,
            'call_id' => $callId,
        ];

        (new DestroyCall)->execute($data);

        return response()->json([
            'data' => true,
        ], 200);
    }
}
