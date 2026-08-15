<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Vaults;

use App\Actions\CreateShareLink;
use App\Actions\RevokeShareLink;
use App\Actions\UpdateShareLink;
use App\Enums\ShareLinkExpiryEnum;
use App\Exceptions\ShareLinkIsRevoked;
use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\ShareLink;
use App\Models\TemplateVersionSection;
use App\Models\Vault;
use App\ViewModels\Vaults\ContactShareLinksViewModel;
use App\ViewModels\Vaults\ShareLinkFormViewModel;
use App\ViewModels\Vaults\ShareLinkViewModel;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The links somebody has opened onto one person's page.
 *
 * Sharing is not editing, so it is its own screen reached from the record rather
 * than a section of it. Nothing here writes to the record: what is decided on
 * these screens is who may read it.
 *
 * The address of a link is shown once, by `show`, out of a flash the create
 * response puts there. Only a hash of the token is stored, so there is no second
 * chance to look it up and the screen says as much rather than pretending.
 */
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

    /**
     * The action writes the inclusions and the exclusions as a whole rather than
     * merging them, so the five inclusions this form does not ask about are sent
     * back as they are. A form that does not ask must not answer.
     */
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
                includesActivities: $shareLink->includes_activities,
                includesActivityNarrative: $shareLink->includes_activity_narrative,
                includesDayNarrative: $shareLink->includes_day_narrative,
                includesRelationships: $shareLink->includes_relationships,
                includesGroups: $shareLink->includes_groups,
                includesSensitiveFields: $request->boolean('includes_sensitive_fields'),
                excludedSectionIds: $this->excludedSectionIds($request, $contact),
                excludedActivityIds: $shareLink->excluded_activity_ids,
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

    /**
     * Revoking, which the row calls what it is. It is a delete in HTTP terms and
     * nothing in the sense that matters: the row stays on the list with its
     * visits, because that history is the answer to who could see what.
     */
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

    /**
     * @return array<string, array<int, string>>
     */
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

    /**
     * `keep` is what the edit form offers instead of guessing which of the four
     * spans a stored date came from, and it can only mean the date already on
     * the link.
     */
    private function expiresAt(Request $request, ?ShareLink $shareLink): ?Carbon
    {
        $expiry = $request->string('expiry')->toString();

        if ($expiry === 'keep') {
            return $shareLink?->expires_at;
        }

        return ShareLinkExpiryEnum::from($expiry)->expiresAt();
    }

    /**
     * The form sends the sections going out, since a box nobody ticks sends
     * nothing at all, and the column stores the ones held back. Everything the
     * record carries and the form did not name is held back, which also means an
     * id from another template cannot let anything through.
     *
     * @return list<int>
     */
    private function excludedSectionIds(Request $request, Contact $contact): array
    {
        $included = array_map('intval', $request->input('sections', []));

        return $contact->templateVersion->assembly
            ->reject(fn (TemplateVersionSection $row): bool => in_array($row->id, $included, true))
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
            ->with('error_description', __('A revoked link is never brought back. Make a new one instead.'));
    }
}
