<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateLifeEvent;
use App\Enums\LifeEventSourceEnum;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\FieldValueIsNotUnique;
use App\Exceptions\LifeEventTypeIsNotAvailable;
use App\Exceptions\NotEnoughRepetitions;
use App\Exceptions\NotEnoughStorageLeft;
use App\Exceptions\TooManyRepetitions;
use App\Http\Controllers\Controller;
use App\Http\Requests\SaveLifeEventRequest;
use App\Models\Contact;
use App\Models\Vault;
use App\ViewModels\Vaults\VaultDashboardViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VaultLifeEventController extends Controller
{
    public function new(Request $request, Vault $vault): View
    {
        return view('app.vaults.show', [
            'viewModel' => new VaultDashboardViewModel(
                user: $request->user(),
                vault: $vault,
                opensLifeEventPanel: true,
            ),
        ]);
    }

    public function create(SaveLifeEventRequest $request, Vault $vault): RedirectResponse
    {
        $validated = [
            ...$request->validated(),
            ...$request->validate(['contact_id' => ['required', 'integer']]),
        ];

        try {
            $contact = Contact::query()
                ->where('vault_id', $vault->id)
                ->findOrFail($validated['contact_id']);
        } catch (ModelNotFoundException) {
            abort(404);
        }

        $lifeEventType = $request->lifeEventType();

        $values = $request->values($lifeEventType->structure?->publishedVersion);

        try {
            $lifeEvent = new CreateLifeEvent(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                lifeEventType: $lifeEventType,
                happenedOn: CarbonImmutable::parse($validated['happened_on']),
                title: $validated['title'] ?? null,
                narrative: $validated['narrative'] ?? null,
                location: $validated['location'] ?? null,
                durationInMinutes: isset($validated['duration_in_minutes']) ? (int) $validated['duration_in_minutes'] : null,
                participants: $request->participants($contact)->all(),
                values: $values,
                createdFrom: LifeEventSourceEnum::Dashboard,
            )->execute();
        } catch (LifeEventTypeIsNotAvailable $e) {
            return back()->withInput()->withErrors(['life_event_type_id' => $e->getMessage()]);
        } catch (FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|NotEnoughStorageLeft|TooManyRepetitions $e) {
            return back()->withInput()->withErrors(['fields' => $e->getMessage()]);
        }

        return redirect()->route('vaults.show', $vault)
            ->with('status', __(':type, :date, is on the timeline of :names.', $lifeEvent->confirmationParts($request->user())))
            ->with('lifeEventJustAdded', $lifeEvent->id);
    }
}
