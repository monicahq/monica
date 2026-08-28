<?php

declare(strict_types=1);

namespace App\ViewModels\GettingStarted;

use App\Models\Contact;
use App\Models\Template;
use App\Models\User;
use App\Models\Vault;

/**
 * The screen somebody lands on before they have made anything: a letter, and
 * the short list of what people usually do first. Each step reads the account
 * and says what it finds, so the list cannot claim something was done that
 * was not, and cannot ask for something twice.
 */
class GettingStartedViewModel
{
    /**
     * The photographs beside the letter, dropped into `public/images`. The
     * signature reads perfectly well without them, so a missing file costs the
     * screen nothing but a circle.
     */
    public const array PORTRAITS = [
        'Regis' => 'images/regis.jpg',
        'Alexis' => 'images/alexis.jpg',
    ];

    public function __construct(
        private readonly User $user,
    ) {}

    public function greeting(): string
    {
        return __('Hello :name, welcome to :app.', [
            'name' => explode(' ', $this->user->first_name)[0],
            'app' => config('app.name'),
        ]);
    }

    public function introduction(): string
    {
        return __('Nothing is here yet. Before you start, a word from the two people who build this.');
    }

    /**
     * The letter, one paragraph at a time.
     *
     * @return array<int, string>
     */
    public function letter(): array
    {
        return [
            __('Hi. We are Regis and Alexis, and between us we write Monica, which has been going on and off since 2017, mostly in the evenings. It started because we kept forgetting the things we actually wanted to remember about people: who was going through a hard time, whose kid had just been born, which friend we had not called in a year.'),
            __('Monica is where you put all of that. It is not a social network and there is nothing to broadcast. It is your own record, kept for you, and you can take it out or delete it whenever you like.'),
            __('Everything starts with a vault, which is simply a sealed compartment for one part of your life. Make one, put a person in it, and see how it feels. If something is missing or annoys you, write to us. We read every message.'),
        ];
    }

    public function founderName(): string
    {
        return __(':first and :second', ['first' => 'Regis', 'second' => 'Alexis']);
    }

    public function founderRole(): string
    {
        return __('The two of us build :app', ['app' => config('app.name')]);
    }

    /**
     * One circle each, in the order they are named in the signature.
     *
     * @return array<int, array{name: string, url: string}>
     */
    public function portraits(): array
    {
        return array_map(
            fn (string $name, string $path): array => ['name' => $name, 'url' => asset($path)],
            array_keys(self::PORTRAITS),
            self::PORTRAITS,
        );
    }

    public function createVaultUrl(): string
    {
        return route('vaults.new');
    }

    /**
     * Adding somebody has no screen to send anyone to yet, so that step names
     * what comes next and points nowhere rather than promising a page that
     * does not exist.
     *
     * @return array<int, array{title: string, description: string, action: string|null, url: string|null, done: bool}>
     */
    public function steps(): array
    {
        return [
            [
                'title' => __('Create your first vault'),
                'description' => __('Everything else lives inside one. Name it after the group of people it holds.'),
                'action' => __('Create'),
                'url' => route('vaults.new'),
                'done' => $this->hasAVault(),
            ],
            [
                'title' => __('Add someone you know'),
                'description' => __('A name is enough to start. The rest can be filled in whenever you learn it.'),
                'action' => null,
                'url' => null,
                'done' => $this->hasAContact(),
            ],
            [
                'title' => __('Decide what a contact looks like'),
                'description' => __('Templates set which sections show on a contact page. The one you were given suits most vaults.'),
                'action' => __('Review'),
                'url' => route('settings.templates.index'),
                'done' => $this->hasATemplateOfTheirOwn(),
            ],
            [
                'title' => __('Finish your profile'),
                'description' => __('Your photo, and the time zone reminders are sent in. Signing up filled in the rest.'),
                'action' => __('Open'),
                'url' => route('settings.profile.index'),
                'done' => $this->user->hasPhoto(),
            ],
            [
                'title' => __('Turn on two factor authentication'),
                'description' => __('This account holds things about other people, which is a good reason to lock it properly.'),
                'action' => __('Set up'),
                'url' => route('settings.security.index'),
                'done' => $this->user->usesTwoFactorAuthentication(),
            ],
        ];
    }

    public function progressLabel(): string
    {
        $steps = $this->steps();

        return __(':done of :total done', [
            'done' => count(array_filter($steps, fn (array $step): bool => $step['done'])),
            'total' => count($steps),
        ]);
    }

    public function importNote(): string
    {
        return __('Coming from Monica v2 or another address book? Import a CSV or a vCard file and we will map the fields with you before anything is written.');
    }

    private function hasAVault(): bool
    {
        return Vault::query()->where('account_id', $this->user->account_id)->exists();
    }

    private function hasAContact(): bool
    {
        return Contact::query()->where('account_id', $this->user->account_id)->exists();
    }

    /**
     * Whether the account went past the template it was given. A supplied
     * template nobody has looked at says nothing about whether anybody decided
     * what a contact should hold; one they built themselves does.
     */
    private function hasATemplateOfTheirOwn(): bool
    {
        return Template::query()
            ->where('account_id', $this->user->account_id)
            ->where('is_system', false)
            ->exists();
    }

    public function user(): User
    {
        return $this->user;
    }
}
