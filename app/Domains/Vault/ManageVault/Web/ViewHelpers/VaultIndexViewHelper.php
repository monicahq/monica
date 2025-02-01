<?php

namespace App\Domains\Vault\ManageVault\Web\ViewHelpers;

use App\Helpers\VaultHelper;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class VaultIndexViewHelper
{
    /**
     * Get all the data needed for the general layout page.
     */
    public static function layoutData(?Vault $vault = null): array
    {
        return [
            'user' => [
                'id' => Auth::id(),
                'name' => Auth::user()->name,
            ],
            'vault' => $vault ? [
                'id' => $vault->id,
                'name' => $vault->name,
                'permission' => [
                    'at_least_editor' => VaultHelper::getPermission(Auth::user(), $vault) <= Vault::PERMISSION_EDIT,
                    'at_least_manager' => VaultHelper::getPermission(Auth::user(), $vault) <= Vault::PERMISSION_MANAGE,
                ],
                'visibility' => [
                    'show_group_tab' => $vault->show_group_tab,
                    'show_tasks_tab' => $vault->show_tasks_tab,
                    'show_files_tab' => $vault->show_files_tab,
                    'show_journal_tab' => $vault->show_journal_tab,
                    'show_companies_tab' => $vault->show_companies_tab,
                    'show_reports_tab' => $vault->show_reports_tab,
                    'show_calendar_tab' => $vault->show_calendar_tab,
                ],
                'url' => [
                    'dashboard' => route('vault.show', [
                        'vault' => $vault->id,
                    ]),
                    'contacts' => route('contact.index', [
                        'vault' => $vault->id,
                    ]),
                    'calendar' => route('vault.calendar.index', [
                        'vault' => $vault->id,
                    ]),
                    'journals' => route('journal.index', [
                        'vault' => $vault->id,
                    ]),
                    'groups' => route('group.index', [
                        'vault' => $vault->id,
                    ]),
                    'companies' => route('vault.companies.index', [
                        'vault' => $vault->id,
                    ]),
                    'tasks' => route('vault.tasks.index', [
                        'vault' => $vault->id,
                    ]),
                    'files' => route('vault.files.index', [
                        'vault' => $vault->id,
                    ]),
                    'reports' => route('vault.reports.index', [
                        'vault' => $vault->id,
                    ]),
                    'settings' => route('vault.settings.index', [
                        'vault' => $vault->id,
                    ]),
                    'search' => route('vault.search.index', [
                        'vault' => $vault->id,
                    ]),
                    'get_most_consulted_contacts' => route('vault.user.search.mostconsulted', [
                        'vault' => $vault->id,
                    ]),
                    'search_contacts_only' => route('vault.user.search.index', [
                        'vault' => $vault->id,
                    ]),
                ],
            ] : null,
            'url' => [
                'vaults' => route('vault.index'),
                'settings' => route('settings.index'),
                'logout' => route('logout'),
            ],
        ];
    }

    public static function data(User $user): array
    {
        $vaults = $user->vaults()
            ->where('account_id', $user->account_id)
            ->with('contacts')
            ->get()
            ->sortByCollator('name')
            ->map(function (Vault $vault): array {
                $randomContactsCollection = self::getContacts($vault);
                $totalContactNumber = $vault->contacts->count();

                return [
                    'id' => $vault->id,
                    'name' => $vault->name,
                    'description' => $vault->description,
                    'contacts' => $randomContactsCollection,
                    'remaining_contacts' => $totalContactNumber - $randomContactsCollection->count(),
                    'url' => [
                        'show' => route('vault.show', [
                            'vault' => $vault,
                        ]),
                        'edit' => route('vault.edit', [
                            'vault' => $vault,
                        ]),
                        'settings' => route('vault.settings.index', [
                            'vault' => $vault->id,
                        ]),
                    ],
                ];
            });

        return [
            'vaults' => $vaults,
            'url' => [
                'vault' => [
                    'create' => route('vault.create'),
                ],
            ],
        ];
    }

    private static function getContacts(Vault $vault): Collection
    {
        return $vault->contacts
            ->random(fn (Collection $items): int => min(5, count($items))) // @phpstan-ignore-line
            ->map(fn (Contact $contact) => [
                'id' => $contact->id,
                'name' => $contact->name,
                'avatar' => $contact->avatar,
            ]);
    }
}
