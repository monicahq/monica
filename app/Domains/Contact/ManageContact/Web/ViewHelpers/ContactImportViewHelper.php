<?php

/**
 * vCard Import Feature - Import View Helper
 *
 * @author Clément ABRAHAM <https://github.com/cabraham2>
 * @version 1.0.0
 * @created 2026-01-08
 * @updated 2026-01-08
 * @license MIT
 *
 * View helper to format data for the import interface.
 * Transforms parsed vCard data for Vue frontend consumption.
 */

namespace App\Domains\Contact\ManageContact\Web\ViewHelpers;

use App\Models\Vault;
use Illuminate\Support\Collection;

class ContactImportViewHelper
{
    /**
     * Get data for the import page.
     */
    public static function data(Vault $vault): array
    {
        return [
            'vault' => [
                'id' => $vault->id,
                'name' => $vault->name,
            ],
            'url' => [
                'upload' => route('contact.import.upload', ['vault' => $vault->id]),
                'contact_detail' => route('contact.import.detail', ['vault' => $vault->id]),
                'import' => route('contact.import.store', ['vault' => $vault->id]),
                'cancel' => route('contact.import.cancel', ['vault' => $vault->id]),
                'back' => route('contact.index', ['vault' => $vault->id]),
            ],
        ];
    }

    /**
     * Format parsed contacts for the frontend.
     */
    public static function formatParsedContacts(Collection $contacts, string $vaultId = null, string $sessionKey = null): array
    {
        return $contacts->map(function ($contact) use ($vaultId, $sessionKey) {
            if ($contact['error']) {
                return [
                    'index' => $contact['index'],
                    'error' => true,
                    'error_message' => $contact['error_message'],
                    'name' => 'Error parsing contact',
                ];
            }

            // Build display name
            $nameParts = array_filter([
                $contact['first_name'] ?? null,
                $contact['middle_name'] ?? null,
                $contact['last_name'] ?? null,
            ]);

            $name = ! empty($nameParts) 
                ? implode(' ', $nameParts) 
                : ($contact['formatted_name'] ?? 'Unnamed Contact');

            // Count data points
            $dataPoints = 0;
            if (! empty($contact['emails'])) {
                $dataPoints += count($contact['emails']);
            }
            if (! empty($contact['phones'])) {
                $dataPoints += count($contact['phones']);
            }
            if (! empty($contact['addresses'])) {
                $dataPoints += count($contact['addresses']);
            }
            if (! empty($contact['organization'])) {
                $dataPoints++;
            }
            if (! empty($contact['title'])) {
                $dataPoints++;
            }
            if (! empty($contact['birthday'])) {
                $dataPoints++;
            }
            if (! empty($contact['note'])) {
                $dataPoints++;
            }
            if (! empty($contact['urls'])) {
                $dataPoints += count($contact['urls']);
            }
            if (! empty($contact['categories'])) {
                $dataPoints++;
            }
            if (! empty($contact['photo'])) {
                $dataPoints++;
            }

            return [
                'index' => $contact['index'],
                'error' => false,
                'name' => $name,
                'first_name' => $contact['first_name'] ?? null,
                'last_name' => $contact['last_name'] ?? null,
                'middle_name' => $contact['middle_name'] ?? null,
                'prefix' => $contact['prefix'] ?? null,
                'suffix' => $contact['suffix'] ?? null,
                'nickname' => $contact['nickname'] ?? null,
                'organization' => $contact['organization'] ?? null,
                'title' => $contact['title'] ?? null,
                'email' => ! empty($contact['emails']) ? $contact['emails'][0]['value'] : null,
                'phone' => ! empty($contact['phones']) ? $contact['phones'][0]['value'] : null,
                'emails' => $contact['emails'] ?? [],
                'phones' => $contact['phones'] ?? [],
                'addresses' => $contact['addresses'] ?? [],
                'urls' => $contact['urls'] ?? [],
                'emails_count' => ! empty($contact['emails']) ? count($contact['emails']) : 0,
                'phones_count' => ! empty($contact['phones']) ? count($contact['phones']) : 0,
                'addresses_count' => ! empty($contact['addresses']) ? count($contact['addresses']) : 0,
                'urls_count' => ! empty($contact['urls']) ? count($contact['urls']) : 0,
                'data_points' => $dataPoints,
                'birthday' => $contact['birthday'] ?? null,
                'note' => $contact['note'] ?? null,
                'has_note' => ! empty($contact['note']),
                'categories' => $contact['categories'] ?? [],
                // Photo URL - will be served via endpoint
                'has_photo' => ! empty($contact['photo']),
                'photo_url' => ! empty($contact['photo']) && $vaultId && $sessionKey 
                    ? route('contact.import.photo', ['vault' => $vaultId, 'sessionKey' => $sessionKey, 'index' => $contact['index']]) 
                    : null,
            ];
        })->values()->toArray();
    }
}
