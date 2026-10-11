<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactLifeEventCandidateController extends Controller
{
    public const int LIMIT = 25;

    public function index(Request $request, Vault $vault, Contact $contact): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $except = array_filter((array) $request->input('except', []), is_numeric(...));

        return view('app.vaults.contacts._life-event-candidates', [
            'listId' => 'life-event-candidates',
            'candidates' => Contact::query()
                ->where('vault_id', $vault->id)
                ->whereKeyNot([$contact->id, ...$except])
                ->matchingSearch((string) $request->string('query'), $request->user()->locale)
                ->orderBy('sort_name')
                ->limit(self::LIMIT)
                ->get()
                ->map(fn (Contact $candidate): array => ['id' => $candidate->id, 'name' => $candidate->name])
                ->all(),
        ]);
    }
}
