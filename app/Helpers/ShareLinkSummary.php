<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Contact;
use App\Models\ShareLink;
use App\Models\StructureVersionSection;
use App\Models\User;

class ShareLinkSummary
{
    public static function showsLine(ShareLink $link, Contact $contact): string
    {
        $shown = self::sectionsShown($link, $contact);
        $total = self::sectionsTotal($contact);

        $sections = $shown === $total
            ? __('Every section of their page')
            : __(':shown of :total sections', ['shown' => $shown, 'total' => $total]);

        if ($link->includes_sensitive_fields) {
            return __(':sections, and the fields you marked sensitive', ['sections' => $sections]);
        }

        return $sections;
    }

    public static function heldBackLine(ShareLink $link, Contact $contact): string
    {
        $held = [];

        $withheld = self::sectionsTotal($contact) - self::sectionsShown($link, $contact);

        if ($withheld > 0) {
            $held[] = trans_choice('{1}One section|:count sections', $withheld);
        }

        if (! $link->includes_sensitive_fields) {
            $held[] = __('The fields you marked sensitive');
        }

        $held[] = __('Anything attached as a file');
        $held[] = __('Who changed what and when, and everything technical');

        return implode(' · ', $held);
    }

    public static function endsLine(ShareLink $link, User $user): string
    {
        if ($link->revoked_at !== null) {
            return __('Revoked :when', ['when' => $link->revoked_at->setTimezone($user->timezone)->isoFormat('LL')]);
        }

        if ($link->expires_at === null) {
            return __('Never, until you revoke it');
        }

        if ($link->expires_at->isPast()) {
            return __('Expired :when', ['when' => $link->expires_at->setTimezone($user->timezone)->isoFormat('LL')]);
        }

        return __(':date, :relative', [
            'date' => $link->expires_at->setTimezone($user->timezone)->isoFormat('LL'),
            'relative' => $link->expires_at->diffForHumans(),
        ]);
    }

    public static function sectionsShown(ShareLink $link, Contact $contact): int
    {
        $included = $link->included_section_ids;

        return $contact->structureVersion->assembly
            ->filter(fn (StructureVersionSection $row): bool => in_array($row->sectionVersion->section_id, $included, true))
            ->count();
    }

    public static function sectionsTotal(Contact $contact): int
    {
        return $contact->structureVersion->assembly->count();
    }
}
