<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateGroupMembership;
use App\Actions\DestroyGroupMembership;
use App\Actions\UpdateGroupMembership;
use App\Enums\DatePrecisionEnum;
use App\Exceptions\ContactIsAlreadyInTheGroup;
use App\Exceptions\MembershipEndsBeforeItStarts;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Vault;
use App\ViewModels\Vaults\GroupMemberFormViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GroupMemberController extends Controller
{
    public function new(Request $request, Vault $vault, Group $group): View
    {
        return view('app.vaults.groups.member-form', [
            'viewModel' => new GroupMemberFormViewModel(user: $request->user(), vault: $vault, group: $group),
        ]);
    }

    public function create(Request $request, Vault $vault, Group $group): RedirectResponse
    {
        $validated = $request->validate(['contact' => ['required', 'string'], ...$this->rules()], [
            'contact.required' => __('Choose who joins the group.'),
        ]);

        $contact = Contact::query()->where('vault_id', $vault->id)->addressedBy($validated['contact'])->firstOrFail();

        try {
            new CreateGroupMembership(
                user: $request->user(),
                vault: $vault,
                group: $group,
                contact: $contact,
                role: $validated['role'] ?? null,
                joinedOn: isset($validated['joined_on']) ? CarbonImmutable::parse($validated['joined_on']) : null,
                joinedPrecision: DatePrecisionEnum::from($validated['joined_precision'] ?? DatePrecisionEnum::Day->value),
                leftOn: isset($validated['left_on']) ? CarbonImmutable::parse($validated['left_on']) : null,
                leftPrecision: DatePrecisionEnum::from($validated['left_precision'] ?? DatePrecisionEnum::Day->value),
            )->execute();
        } catch (ContactIsAlreadyInTheGroup $e) {
            return back()->withInput()->withErrors(['contact' => $e->getMessage()]);
        } catch (MembershipEndsBeforeItStarts $e) {
            return back()->withInput()->withErrors(['left_on' => $e->getMessage()]);
        }

        return redirect()->route('groups.show', ['vault' => $vault, 'group' => $group])
            ->with('status', __(':name is in the group.', ['name' => $contact->name]));
    }

    public function edit(Request $request, Vault $vault, Group $group, GroupMember $membership): View
    {
        abort_unless($membership->group_id === $group->id, 404);

        return view('app.vaults.groups.member-form', [
            'viewModel' => new GroupMemberFormViewModel(user: $request->user(), vault: $vault, group: $group, membership: $membership),
        ]);
    }

    public function update(Request $request, Vault $vault, Group $group, GroupMember $membership): RedirectResponse
    {
        abort_unless($membership->group_id === $group->id, 404);

        $validated = $request->validate($this->rules());

        try {
            new UpdateGroupMembership(
                user: $request->user(),
                vault: $vault,
                membership: $membership,
                role: $validated['role'] ?? null,
                joinedOn: isset($validated['joined_on']) ? CarbonImmutable::parse($validated['joined_on']) : null,
                joinedPrecision: DatePrecisionEnum::from($validated['joined_precision'] ?? DatePrecisionEnum::Day->value),
                leftOn: isset($validated['left_on']) ? CarbonImmutable::parse($validated['left_on']) : null,
                leftPrecision: DatePrecisionEnum::from($validated['left_precision'] ?? DatePrecisionEnum::Day->value),
            )->execute();
        } catch (ContactIsAlreadyInTheGroup|MembershipEndsBeforeItStarts $e) {
            return back()->withInput()->withErrors(['left_on' => $e->getMessage()]);
        }

        return redirect()->route('groups.show', ['vault' => $vault, 'group' => $group])
            ->with('status', __('The membership is saved.'));
    }

    public function destroy(Request $request, Vault $vault, Group $group, GroupMember $membership): RedirectResponse
    {
        abort_unless($membership->group_id === $group->id, 404);

        new DestroyGroupMembership(
            user: $request->user(),
            vault: $vault,
            membership: $membership,
        )->execute();

        return redirect()->route('groups.show', ['vault' => $vault, 'group' => $group])
            ->with('status', __('The membership is deleted.'));
    }

    /** @return array<string, array<int, mixed>> */
    private function rules(): array
    {
        return [
            'role' => ['nullable', 'string', 'max:255'],
            'joined_on' => ['nullable', 'date_format:Y-m-d'],
            'joined_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
            'left_on' => ['nullable', 'date_format:Y-m-d'],
            'left_precision' => ['nullable', Rule::enum(DatePrecisionEnum::class)],
        ];
    }
}
