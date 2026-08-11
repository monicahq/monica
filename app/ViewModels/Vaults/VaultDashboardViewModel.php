<?php

declare(strict_types=1);

namespace App\ViewModels\Vaults;

use App\Enums\TemplateAppliesToEnum;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use App\Models\VaultDefaultTemplate;

/**
 * What one vault shows when you open it: one box for every part of it, so the
 * screen is a map of the vault rather than a summary of it.
 *
 * Two of the six parts exist. The people are real, and so are the settings. The
 * journal, the tasks, the reminders and the files are each their own model and
 * none of them has been written, so those four boxes say what will land in them
 * and are not offered as somewhere to go. This screen used to fill them with
 * invented rows instead, which read as somebody else's life the moment you
 * looked at it, and made the four parts that do not work look exactly like the
 * two that do.
 *
 * A person's row carries their nickname and nothing else. The design asks it to
 * say how you know them and when their birthday is; relationships and dates are
 * both later models, and a row of em-dashes is worse than a shorter row.
 */
class VaultDashboardViewModel
{
    /**
     * How many people the box shows before it gives up and points at the whole
     * list. Enough to recognise the vault, few enough that the box stays the
     * height of the ones beside it.
     */
    private const int PEOPLE_SHOWN = 5;

    public function __construct(
        private readonly User $user,
        private readonly Vault $vault,
    ) {}

    public function vault(): Vault
    {
        return $this->vault;
    }

    public function user(): User
    {
        return $this->user;
    }

    public function title(): string
    {
        return $this->vault->name;
    }

    public function description(): ?string
    {
        return $this->vault->description;
    }

    /**
     * The line above the title: whose vault it is and how many people are in
     * it. It is what tells two vaults of the same shape apart at a glance.
     */
    public function overline(): string
    {
        return "{$this->user->name} · {$this->contactCount()}";
    }

    public function vaultsUrl(): string
    {
        return route('vaults.index');
    }

    public function newContactUrl(): string
    {
        return route('contacts.new', $this->vault);
    }

    public function allContactsUrl(): string
    {
        return route('contacts.index', $this->vault);
    }

    public function settingsUrl(): string
    {
        return route('vaults.settings.index', $this->vault);
    }

    /**
     * The people the box shows: the last ones written down, since the reason to
     * open a vault is usually the thing you were last doing in it.
     *
     * @return array<int, array{name: string, nickname: string}>
     */
    public function people(): array
    {
        return $this->vault
            ->contacts()
            ->listed()
            ->orderByDesc('created_at')
            ->limit(self::PEOPLE_SHOWN)
            ->get()
            ->map(fn (Contact $contact): array => [
                'name' => $contact->name,
                'nickname' => (string) $contact->nickname,
            ])
            ->all();
    }

    /**
     * The line beside the heading of the people box, which is the count of
     * them, and the line at the foot of it, which is the way to all of them.
     */
    public function peopleCaption(): string
    {
        return $this->contactCount();
    }

    public function allPeopleLabel(): string
    {
        if ($this->vault->contact_count <= self::PEOPLE_SHOWN) {
            return __('The whole list');
        }

        return __('All :count people', ['count' => $this->vault->contact_count]);
    }

    /**
     * The two things the settings of a vault settle today. The design asks for
     * two more, who else can see the vault and what it is called, and neither
     * has anything behind it: a vault is not shared with anybody yet, and its
     * wording is only set when it is made.
     *
     * @return array<int, array{title: string, line: string}>
     */
    public function settingsRows(): array
    {
        return [
            [
                'title' => __('What a new contact starts from'),
                'line' => $this->currentTemplateName() ?? __('Nothing is set, so whoever writes somebody down picks a template themselves.'),
            ],
            [
                'title' => __('Delete this vault'),
                'line' => $this->deletionConsequence(),
            ],
        ];
    }

    /**
     * The parts of a vault that have no model yet. Each one is drawn as what it
     * will eventually hold, in the colour that part of the product answers to,
     * and none of them is a link: a word that does not move is better than a
     * link that leads nowhere.
     *
     * @return array<int, array{title: string, caption: string, titleClass: string, iconClass: string, icon: string, emptyTitle: string, emptyText: string}>
     */
    public function unbuiltSections(): array
    {
        return [
            [
                'title' => __('Journal'),
                'caption' => __('No days written'),
                'titleClass' => 'text-indigo',
                'iconClass' => 'bg-indigo-tint text-indigo',
                'icon' => 'journal',
                'emptyTitle' => __('No days written down'),
                'emptyText' => __('A day is a page. Anybody you name in it is written into their own history at the same time.'),
            ],
            [
                'title' => __('Tasks'),
                'caption' => __('Nothing open'),
                'titleClass' => 'text-forest',
                'iconClass' => 'bg-forest-tint text-forest',
                'icon' => 'tasks',
                'emptyTitle' => __('Nothing you owe anyone'),
                'emptyText' => __('Things you said you would do for someone in this vault land here until they are done.'),
            ],
            [
                'title' => __('Reminders'),
                'caption' => __('No dates set'),
                'titleClass' => 'text-plum',
                'iconClass' => 'bg-plum-tint text-plum',
                'icon' => 'reminder',
                'emptyTitle' => __('No dates to keep'),
                'emptyText' => __('Birthdays and anything you ask to be told about, so December is not a panic.'),
            ],
            [
                'title' => __('Files'),
                'caption' => __('No files'),
                'titleClass' => 'text-sky',
                'iconClass' => 'bg-sky-tint text-sky',
                'icon' => 'file',
                'emptyTitle' => __('No files yet'),
                'emptyText' => __('Photographs, scans and documents you attach to somebody show up here as well.'),
            ],
        ];
    }

    /**
     * How many people are in the vault, said the way somebody would say it.
     * The count is kept on the vault rather than counted here, so a list of
     * vaults does not aggregate once per row.
     */
    private function contactCount(): string
    {
        if ($this->vault->contact_count === 0) {
            return __('Nobody in it yet');
        }

        if ($this->vault->contact_count === 1) {
            return __('1 person');
        }

        return __(':count people', ['count' => $this->vault->contact_count]);
    }

    /**
     * What deleting the vault would take with it. The consequence is named
     * rather than counted at the reader, so the row says what goes before the
     * screen that asks does.
     */
    private function deletionConsequence(): string
    {
        if ($this->vault->contact_count === 0) {
            return __('Takes the vault and everything in it.');
        }

        if ($this->vault->contact_count === 1) {
            return __('Takes the one person in it and everything written about them.');
        }

        return __('Takes :count people and everything written about them.', ['count' => $this->vault->contact_count]);
    }

    /**
     * What the vault currently starts its contacts on, said by name. A vault
     * always has one, but a vault whose default was deleted from underneath it
     * has none to name.
     */
    private function currentTemplateName(): ?string
    {
        return VaultDefaultTemplate::query()
            ->where('vault_id', $this->vault->id)
            ->where('applies_to', TemplateAppliesToEnum::Contact)
            ->first()
            ?->templateVersion
            ->template
            ->name;
    }
}
