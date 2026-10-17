<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateShareLink;
use App\Actions\RevokeShareLink;
use App\Actions\UpdateShareLink;
use App\Enums\ShareLinkExpiryEnum;
use App\Exceptions\ShareLinkIsRevoked;
use App\Helpers\PageSize;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ShareLink;
use App\Models\StructureVersionSection;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactShareLinksViewModel;
use App\ViewModels\Vaults\ShareLinkFormViewModel;
use App\ViewModels\Vaults\ShareLinkViewModel;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ContactShareLinkController extends Controller
{
    public function index(Request $request, Vault $vault, Contact $contact): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        return view('app.vaults.contacts.share-links.index', [
            'viewModel' => new ContactShareLinksViewModel(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                size: PageSize::from($request, ContactShareLinksViewModel::PER_PAGE),
            ),
        ]);
    }

    public function new(Request $request, Vault $vault, Contact $contact): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        return view('app.vaults.contacts.share-links.form', [
            'viewModel' => new ShareLinkFormViewModel(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
            ),
        ]);
    }

    public function create(Request $request, Vault $vault, Contact $contact): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);

        $validated = $request->validate($this->rules($contact));

        $shareLink = new CreateShareLink(
            user: $request->user(),
            shareable: $contact,
            label: $validated['label'],
            recipientHint: $validated['recipient_hint'] ?? null,
            password: $validated['password'] ?? null,
            expiresAt: $this->expiresAt($request, null),
            includesSensitiveFields: $request->boolean('includes_sensitive_fields'),
            excludedSectionIds: $this->excludedSectionIds($request, $contact),
        )->execute();

        return redirect()->route('contacts.shareLinks.show', [
            'vault' => $vault,
            'contact' => $contact,
            'shareLink' => $shareLink,
        ])->with('shareLinkUrl', route('share.show', ['token' => $shareLink->plainToken]));
    }

    public function show(Request $request, Vault $vault, Contact $contact, ShareLink $shareLink): View
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);
        abort_unless($this->isOn($shareLink, $contact), 404);

        return view('app.vaults.contacts.share-links.show', [
            'viewModel' => new ShareLinkViewModel(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                shareLink: $shareLink,
                size: PageSize::from($request, ShareLinkViewModel::PER_PAGE),
            ),
        ]);
    }

    public function edit(Request $request, Vault $vault, Contact $contact, ShareLink $shareLink): View|RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);
        abort_unless($this->isOn($shareLink, $contact), 404);

        if ($shareLink->revoked_at !== null) {
            return $this->refuseTheRevoked($vault, $contact);
        }

        return view('app.vaults.contacts.share-links.form', [
            'viewModel' => new ShareLinkFormViewModel(
                user: $request->user(),
                vault: $vault,
                contact: $contact,
                shareLink: $shareLink,
            ),
        ]);
    }

    public function update(Request $request, Vault $vault, Contact $contact, ShareLink $shareLink): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);
        abort_unless($this->isOn($shareLink, $contact), 404);

        $validated = $request->validate($this->rules($contact, editing: true));

        try {
            new UpdateShareLink(
                user: $request->user(),
                shareLink: $shareLink,
                label: $validated['label'],
                recipientHint: $validated['recipient_hint'] ?? null,
                expiresAt: $this->expiresAt($request, $shareLink),
                password: $validated['password'] ?? null,
                removesPassword: $request->boolean('removes_password'),
                includesFiles: $shareLink->includes_files,
                includesLifeEvents: $shareLink->includes_life_events,
                includesLifeEventNarrative: $shareLink->includes_life_event_narrative,
                includesDayNarrative: $shareLink->includes_day_narrative,
                includesRelationships: $shareLink->includes_relationships,
                includesGroups: $shareLink->includes_groups,
                includesSensitiveFields: $request->boolean('includes_sensitive_fields'),
                excludedSectionIds: $this->excludedSectionIds($request, $contact),
                excludedLifeEventIds: $shareLink->excluded_life_event_ids,
            )->execute();
        } catch (ShareLinkIsRevoked) {
            return $this->refuseTheRevoked($vault, $contact);
        }

        return redirect()->route('contacts.shareLinks.show', [
            'vault' => $vault,
            'contact' => $contact,
            'shareLink' => $shareLink,
        ])
            ->with('status', __('The link is changed.'))
            ->with('status_description', __('Whoever holds it sees the change on their next click. The address itself is the same.'));
    }

    public function destroy(Request $request, Vault $vault, Contact $contact, ShareLink $shareLink): RedirectResponse
    {
        abort_unless($contact->isReachableThrough($request->user(), $vault), 404);
        abort_unless($this->isOn($shareLink, $contact), 404);

        try {
            new RevokeShareLink(
                user: $request->user(),
                shareLink: $shareLink,
            )->execute();
        } catch (ShareLinkIsRevoked) {
            return $this->refuseTheRevoked($vault, $contact);
        }

        return redirect()->route('contacts.shareLinks.index', ['vault' => $vault, 'contact' => $contact])
            ->with('status', __('The link is revoked.'))
            ->with('status_description', __('The address stopped working. Its row stays on the list with the visits it had.'));
    }

    /** @return array<string, array<int, string>> */
    private function rules(Contact $contact, bool $editing = false): array
    {
        $spans = array_map(fn (ShareLinkExpiryEnum $case): string => $case->value, ShareLinkExpiryEnum::cases());

        if ($editing) {
            $spans[] = 'keep';
        }

        return [
            'label' => ['required', 'string', 'max:255'],
            'recipient_hint' => ['nullable', 'string', 'max:255'],
            'expiry' => ['required', 'string', 'in:'.implode(',', $spans)],
            'password' => ['nullable', 'string', 'min:8', 'max:255'],
            'removes_password' => ['nullable', 'boolean'],
            'includes_sensitive_fields' => ['nullable', 'boolean'],
            'sections' => ['nullable', 'array'],
            'sections.*' => ['integer'],
        ];
    }

    private function expiresAt(Request $request, ?ShareLink $shareLink): ?CarbonImmutable
    {
        $expiry = $request->string('expiry')->toString();

        if ($expiry === 'keep') {
            return $shareLink?->expires_at;
        }

        return ShareLinkExpiryEnum::from($expiry)->expiresAt();
    }

    /** @return list<int> */
    private function excludedSectionIds(Request $request, Contact $contact): array
    {
        $included = array_map('intval', $request->input('sections', []));

        return $contact->structureVersion->assembly
            ->reject(fn (StructureVersionSection $row): bool => in_array($row->id, $included, true))
            ->pluck('id')
            ->values()
            ->all();
    }

    private function isOn(ShareLink $shareLink, Contact $contact): bool
    {
        return $shareLink->shareable_type === $contact->getMorphClass()
            && $shareLink->shareable_id === $contact->id;
    }

    private function refuseTheRevoked(Vault $vault, Contact $contact): RedirectResponse
    {
        return redirect()->route('contacts.shareLinks.index', ['vault' => $vault, 'contact' => $contact])
            ->with('error', __('That link was already revoked.'))
            ->with('error_description', __('A revoked link never works again. Make a new one instead.'));
    }
}
