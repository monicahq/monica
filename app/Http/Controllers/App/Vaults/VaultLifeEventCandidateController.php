<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VaultLifeEventCandidateController extends Controller
{
    public const int LIMIT = 25;

    public function index(Request $request, Vault $vault): View
    {
        $except = array_filter((array) $request->input('except', []), is_numeric(...));

        $validated = $request->validate([
            'list' => ['nullable', 'string', 'in:life-event-candidates,life-event-subjects'],
        ]);

        return view('app.vaults.contacts._life-event-candidates', [
            'listId' => $validated['list'] ?? 'life-event-candidates',
            'candidates' => Contact::query()
                ->where('vault_id', $vault->id)
                ->whereKeyNot($except)
                ->matchingSearch((string) $request->string('query'), $request->user()->locale)
                ->orderBy('sort_name')
                ->limit(self::LIMIT)
                ->get()
                ->map(fn (Contact $candidate): array => ['id' => $candidate->id, 'name' => $candidate->name])
                ->all(),
        ]);
    }
}
