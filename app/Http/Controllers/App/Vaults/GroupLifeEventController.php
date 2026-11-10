<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateLifeEvent;
use App\Enums\LifeEventSourceEnum;
use App\Exceptions\FieldIsRequired;
use App\Exceptions\FieldValueIsNotUnique;
use App\Exceptions\LifeEventTypeIsNotAvailable;
use App\Exceptions\NotEnoughRepetitions;
use App\Exceptions\TooManyRepetitions;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Group;
use App\Models\LifeEventType;
use App\Models\Vault;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class GroupLifeEventController extends Controller
{
    public function create(Request $request, Vault $vault, Group $group): RedirectResponse
    {
        $validated = $request->validate([
            'life_event_type_id' => ['required', 'integer'],
            'happened_on' => ['required', 'date'],
            'title' => ['nullable', 'string', 'max:255'],
            'narrative' => ['nullable', 'string', 'max:65535'],
            'participants' => ['required', 'array', 'min:1'],
            'participants.*' => ['integer', 'distinct'],
        ], [
            'participants.required' => __('Tick at least one person who was there.'),
            'participants.min' => __('Tick at least one person who was there.'),
        ]);

        $lifeEventType = LifeEventType::query()
            ->where('account_id', $request->user()->account_id)
            ->findOrFail($validated['life_event_type_id']);

        $ids = array_map(intval(...), $validated['participants']);
        $found = Contact::query()->where('vault_id', $vault->id)->whereIn('id', $ids)->get()->keyBy('id');

        abort_unless($found->count() === count($ids), 404);

        $people = array_map(fn (int $id): Contact => $found[$id], $ids);

        try {
            $lifeEvent = new CreateLifeEvent(
                user: $request->user(),
                vault: $vault,
                contact: $people[0],
                lifeEventType: $lifeEventType,
                happenedOn: CarbonImmutable::parse($validated['happened_on']),
                title: $validated['title'] ?? null,
                narrative: $validated['narrative'] ?? null,
                participants: array_slice($people, 1),
                createdFrom: LifeEventSourceEnum::Group,
                group: $group,
            )->execute();
        } catch (LifeEventTypeIsNotAvailable|FieldIsRequired|FieldValueIsNotUnique|NotEnoughRepetitions|TooManyRepetitions $e) {
            return back()->withInput()->withErrors(['life_event_type_id' => $e->getMessage()]);
        }

        return redirect()->route('groups.show', ['vault' => $vault, 'group' => $group])
            ->with('status', __(':type, :date, is on the timeline of :names.', $lifeEvent->confirmationParts($request->user())))
            ->with('lifeEventJustAdded', $lifeEvent->id);
    }
}
