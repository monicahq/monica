<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateGroup;
use App\Actions\CreateGroupMembership;
use App\Actions\UpdateContactGroups;
use App\Enums\DatePrecisionEnum;
use App\Exceptions\MembershipEndsBeforeItStarts;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Group;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactGroupsFormViewModel;
use App\ViewModels\Vaults\GroupFormViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ContactGroupController extends Controller
{
    public function edit(Request $request, Vault $vault, Contact $contact): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        return view('app.vaults.contacts.groups.edit', [
            'viewModel' => new ContactGroupsFormViewModel(user: $request->user(), vault: $vault, contact: $contact),
        ]);
    }

    public function update(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $validated = $request->validate([
            'groups' => ['nullable', 'array'],
            'groups.*' => ['string'],
        ]);

        $uuids = collect($validated['groups'] ?? [])->filter(fn (string $uuid): bool => Str::isUuid($uuid))->all();

        try {
            new UpdateContactGroups(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                groups: Group::query()->where('vault_id', $vault->id)->whereIn('uuid', $uuids)->get(),
            )->execute();
        } catch (MembershipEndsBeforeItStarts $e) {
            return back()->withInput()->withErrors(['groups' => $e->getMessage()]);
        }

        return redirect()->route('contacts.show', ['vault' => $vault, 'contact' => $contact])
            ->with('status', __('The groups of :name are saved.', ['name' => $contact->name]));
    }

    public function new(Request $request, Vault $vault, Contact $contact): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        return view('app.vaults.groups.form', [
            'viewModel' => new GroupFormViewModel(user: $request->user(), vault: $vault, contact: $contact),
        ]);
    }

    public function create(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:65535'],
            'started_on' => ['nullable', 'date_format:Y-m-d'],
            'started_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
            'ended_on' => ['nullable', 'date_format:Y-m-d'],
            'ended_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
        ]);

        $group = DB::transaction(function () use ($request, $vault, $contact, $validated): Group {
            $group = new CreateGroup(
                user: $request->user(),
                vault: $vault,
                name: $validated['name'],
                description: $validated['description'] ?? null,
                startedOn: isset($validated['started_on']) ? CarbonImmutable::parse($validated['started_on']) : null,
                startedPrecision: DatePrecisionEnum::from($validated['started_precision'] ?? DatePrecisionEnum::Day->value),
                endedOn: isset($validated['ended_on']) ? CarbonImmutable::parse($validated['ended_on']) : null,
                endedPrecision: DatePrecisionEnum::from($validated['ended_precision'] ?? DatePrecisionEnum::Day->value),
            )->execute();

            new CreateGroupMembership(
                user: $request->user(),
                vault: $vault,
                group: $group,
                contact: $contact,
                joinedOn: CarbonImmutable::today(),
                joinedPrecision: DatePrecisionEnum::Day,
            )->execute();

            return $group;
        });

        return redirect()->route('groups.show', ['vault' => $vault, 'group' => $group])
            ->with('status', __('The group :name is created with :contact in it.', ['name' => $group->name, 'contact' => $contact->name]));
    }
}
