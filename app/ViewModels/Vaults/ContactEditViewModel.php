<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Actions\ChangeContactTemplate;
use App\Enums\StructureAppliesToEnum;
use App\Enums\VersionStatusEnum;
use App\Helpers\FlagState;
use App\Helpers\TemplateChangePreview;
use App\Models\Contact;
use App\Models\Relationship;
use App\Models\StructureVersion;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\Builder;

class ContactEditViewModel
{
    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
        private readonly Contact $contact,
        private readonly ?string $askedTemplate = null,
        private readonly ?int $askedVersion = null,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function contact(): Contact
    {
        return $this->contact;
    }

    public function title(): string
    {
        return __('Edit contact');
    }

    public function description(): string
    {
        return __('What the template does not decide about :name.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    public function templateLabel(): string
    {
        return __('Template used for :name', ['name' => $this->shortName()]);
    }

    public function templateSubtitle(): string
    {
        return __('Decides which sections and fields :name’s page shows.', ['name' => $this->shortName()]);
    }

    public function templateHelp(): string
    {
        return __('The template decides which sections and fields :name’s page shows. Switching changes the layout, not what you have written.', ['name' => $this->shortName()]);
    }

    public function currentTemplateName(): string
    {
        $owner = $this->contact->structureVersion->structure->owner;

        return $owner instanceof Template ? $owner->name : __('A template that was deleted');
    }

    public function currentVersionLabel(): ?string
    {
        $count = StructureVersion::query()
            ->where('structure_id', $this->contact->structureVersion->structure_id)
            ->where('status', VersionStatusEnum::Published)
            ->count();

        if ($count < 2) {
            return null;
        }

        return __('Version :number', ['number' => $this->contact->structureVersion->version_number]);
    }

    public function showsTemplatePanel(): bool
    {
        foreach ($this->templateChoices() as $choice) {
            foreach ($choice['versions'] as $version) {
                if (! $version['isCurrent']) {
                    return true;
                }
            }
        }

        return false;
    }

    /** @return list<array{name: string, countLabel: string|null, versions: list<array{label: string, hint: string, url: string, isCurrent: bool}>}> */
    public function templateChoices(): array
    {
        return once(function (): array {
            $templates = Template::query()
                ->where('account_id', $this->user->account_id)
                ->where('is_active', true)
                ->whereHas('structure', function (Builder $query): void {
                    $query->where('applies_to', StructureAppliesToEnum::Contact)
                        ->whereNotNull('published_version_id');
                })
                ->with('structure')
                ->orderBy('position')
                ->get();

            $versions = StructureVersion::query()
                ->whereIn('structure_id', $templates->map(fn (Template $template): int => $template->structure->id))
                ->where('status', VersionStatusEnum::Published)
                ->orderByDesc('version_number')
                ->get()
                ->groupBy('structure_id');

            return $templates
                ->map(function (Template $template) use ($versions): array {
                    $published = $versions->get($template->structure->id, collect())->values();
                    $several = $published->count() > 1;

                    return [
                        'name' => $template->name,
                        'countLabel' => $several ? trans_choice(':count version|:count versions', $published->count()) : null,
                        'versions' => $published
                            ->map(fn (StructureVersion $version, int $index): array => [
                                'label' => $several ? __('Version :number', ['number' => $version->version_number]) : $template->name,
                                'hint' => $this->hintFor($version, $index, $several),
                                'url' => route('contacts.edit', [
                                    'vault' => $this->vault,
                                    'contact' => $this->contact,
                                    'template' => $template->uuid,
                                    'version' => $version->id,
                                ]),
                                'isCurrent' => $version->id === $this->contact->structure_version_id,
                            ])
                            ->all(),
                    ];
                })
                ->values()
                ->all();
        });
    }

    /** @return array{title: string, sentence: string, action: string, template: string, versionId: int, cancelUrl: string, refusal: string|null}|null */
    public function pendingChange(): ?array
    {
        return once(function (): ?array {
            if ($this->askedTemplate === null) {
                return null;
            }

            $template = Template::query()
                ->where('account_id', $this->user->account_id)
                ->where('uuid', $this->askedTemplate)
                ->with('structure.publishedVersion')
                ->first();

            if (! $template instanceof Template) {
                return null;
            }

            $named = $this->askedVersion === null
                ? null
                : StructureVersion::query()->where('account_id', $this->user->account_id)->whereKey($this->askedVersion)->first();

            if ($this->askedVersion !== null && $named === null) {
                return null;
            }

            $version = $named ?? $template->structure->publishedVersion;

            if (! $version instanceof StructureVersion) {
                return null;
            }

            if ($version->id === $this->contact->structure_version_id) {
                return null;
            }

            $several = StructureVersion::query()
                ->where('structure_id', $template->structure->id)
                ->where('status', VersionStatusEnum::Published)
                ->count() > 1;

            return [
                'title' => $several
                    ? __('Switch :name to the :template template (version :number)?', ['name' => $this->shortName(), 'template' => $template->name, 'number' => $version->version_number])
                    : __('Switch :name to the :template template?', ['name' => $this->shortName(), 'template' => $template->name]),
                'sentence' => __('Changing the template will update the sections and fields displayed on :name’s page.', ['name' => $this->shortName()]),
                'action' => route('contacts.template.update', ['vault' => $this->vault, 'contact' => $this->contact]),
                'template' => $template->uuid,
                'versionId' => $version->id,
                'cancelUrl' => route('contacts.edit', ['vault' => $this->vault, 'contact' => $this->contact]),
                'refusal' => ChangeContactTemplate::refusal($template, $named),
            ];
        });
    }

    /** @return list<array{title: string, lines: list<array{text: string, section: string|null, count: string|null}>, note: string|null}>|null */
    public function preview(): ?array
    {
        $pending = $this->pendingChange();

        if ($pending === null || $pending['refusal'] !== null) {
            return null;
        }

        $preview = new TemplateChangePreview(
            contact: $this->contact,
            target: StructureVersion::query()->findOrFail($pending['versionId']),
            namesSensitiveFields: true,
        )->toArray();

        $answers = fn (array $fields): array => array_map(fn (array $field): array => [
            'text' => $field['label'],
            'section' => $field['section'],
            'count' => $field['count'] > 1 ? trans_choice('(:count answer)|(:count answers)', $field['count']) : null,
        ], $fields);

        $reminders = fn (array $rules): array => array_map(fn (array $rule): array => [
            'text' => $rule['label'],
            'section' => null,
            'count' => null,
        ], $rules);

        return array_values(array_filter([
            [
                'title' => __('Information moving to Unused answers'),
                'lines' => $answers($preview['setAside']),
                'note' => __('These fields have no match in the new template, so they will be set aside in Unused answers.'),
            ],
            ['title' => __('Information coming back'), 'lines' => $answers($preview['broughtBack']), 'note' => null],
            ['title' => __('Reminders that stop'), 'lines' => $reminders($preview['remindersStopped']), 'note' => null],
            ['title' => __('Reminders that run again'), 'lines' => $reminders($preview['remindersRestarted']), 'note' => null],
        ], fn (array $group): bool => $group['lines'] !== []));
    }

    public function consequencesLead(): string
    {
        return __('The template decides which fields :name’s page holds. Changing it changes the page, not what you wrote down.', ['name' => $this->shortName()]);
    }

    /** @return list<array{icon: string, text: string}> */
    public function consequences(): array
    {
        return [
            ['icon' => 'arrow-right', 'text' => __('An answer moves to the matching field of the new template, when there is one.')],
            ['icon' => 'layers', 'text' => __('An answer with no matching field goes to Unused answers. Nothing is deleted, and the answer comes back if the field is added to the template later.')],
            ['icon' => 'reminder', 'text' => __('A reminder on a date that goes to Unused answers stops. It starts again when the date comes back.')],
            ['icon' => 'profile', 'text' => __('The name and the picture do not change.')],
            ['icon' => 'clock', 'text' => __('A copy of the page as it is now is kept in its version history.')],
        ];
    }

    private function hintFor(StructureVersion $version, int $index, bool $several): string
    {
        if (! $several) {
            return '';
        }

        if ($index === 0) {
            return __('Latest');
        }

        return $version->published_at?->isoFormat('MMMM YYYY') ?? '';
    }

    public function action(): string
    {
        return route('contacts.name.update', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function avatarDescription(): string
    {
        return __('A square photo reads best. JPEG, PNG or WebP, up to 5 MB. Without one, :name keeps their initials on a coloured background.', [
            'name' => $this->contact->first_name ?? $this->contact->name,
        ]);
    }

    public function hasAvatar(): bool
    {
        return $this->contact->hasAvatar();
    }

    public function avatarUrl(): ?string
    {
        if ($this->contact->avatar === null) {
            return null;
        }

        return route('files.show', ['vault' => $this->vault, 'file' => $this->contact->avatar]);
    }

    public function uploadsAreOn(): bool
    {
        return FlagState::isOn('file-upload', $this->user->account);
    }

    public function updateAvatarUrl(): string
    {
        return route('contacts.avatar.update', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function destroyAvatarUrl(): string
    {
        return route('contacts.avatar.destroy', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function destroyUrl(): string
    {
        return route('contacts.destroy', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function mayBeDeleted(): bool
    {
        return $this->user->can('delete', $this->contact);
    }

    public function deleteDescription(): string
    {
        $response = $this->user->inspect('delete', $this->contact);

        return $response->denied()
            ? (string) $response->message()
            : __('They wait 30 days in the trash of this vault, where they can be restored.');
    }

    /** @return array{title: string, lead: string, lines: list<array{icon: string, text: string}>, url: string, confirmTitle: string, confirmBody: string, reachabilityLine: string|null, refusal: string|null}|null */
    public function demotion(): ?array
    {
        if ($this->contact->is_partial) {
            return null;
        }

        $name = $this->contact->name;
        $answer = $this->user->inspect('demote', $this->contact);
        $isReached = Relationship::query()
            ->where(fn (Builder $query): Builder => $query
                ->where('contact_a_id', $this->contact->id)
                ->orWhere('contact_b_id', $this->contact->id))
            ->exists();

        return [
            'title' => __('Keep :name as a partial contact', ['name' => $name]),
            'lead' => __('For somebody you only need to know through another person, like a colleague’s partner or a friend’s child.'),
            'lines' => [
                ['icon' => 'people', 'text' => __(':name leaves your list of people and no longer counts towards your plan.', ['name' => $name])],
                ['icon' => 'check', 'text' => __('Everything written about :name stays, and so do their relationships.', ['name' => $name])],
                ['icon' => 'arrow-right', 'text' => __('You can make :name a full contact again at any time.', ['name' => $name])],
            ],
            'url' => route('contacts.partial.update', ['vault' => $this->vault, 'contact' => $this->contact]),
            'confirmTitle' => __('Make :name a partial contact?', ['name' => $name]),
            'confirmBody' => __(':name leaves the list of people and the address books synchronised to your phones, and their birthday leaves your birthday calendar. Everything written about them stays, and they can still be found in search.', ['name' => $name]),
            'reachabilityLine' => $isReached
                ? null
                : __(':name has no relationship, so you will find them only under Partial contacts in the list of people, or in search.', ['name' => $name]),
            'refusal' => $answer->allowed() ? null : $answer->message(),
        ];
    }

    public function contactUrl(): string
    {
        return route('contacts.show', ['vault' => $this->vault, 'contact' => $this->contact]);
    }

    public function nameEditor(): ContactNameEditorBlock
    {
        return new ContactNameEditorBlock(contact: $this->contact);
    }

    public function shortName(): string
    {
        return $this->contact->first_name ?? $this->contact->name;
    }

    public function value(string $field): ?string
    {
        return old($field, $this->contact->$field);
    }
}
