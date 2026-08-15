<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Contact;
use App\Models\ShareLink;
use App\Models\TemplateVersionSection;

/**
 * What a link lets through, said in words.
 *
 * Three screens need the same sentences and have to agree about them: the list
 * of links says what each one shows, the page of one link recaps it, and the
 * page a recipient opens says when it ends. Somebody who reads "3 of 5 sections"
 * on the list and something else on the link itself has no reason to believe
 * either, so the sentences live in one place.
 *
 * Everything not named as going out is held back, and the sentences are written
 * that way round on purpose: the product's promise is that a link shows the
 * least until somebody says otherwise, and copy that lists what is included is
 * how that promise stays checkable.
 */
class ShareLinkSummary
{
    /**
     * What the recipient gets. Sections first, since they are the bulk of the
     * page, then the one inclusion this product can honour today.
     */
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

    /**
     * What stays here whatever the form said. Most of this list is not a choice
     * the owner made: it is what the product cannot hand to a link at all yet,
     * and saying so is better than letting somebody assume it went.
     */
    public static function heldBackLine(ShareLink $link, Contact $contact): string
    {
        $held = [];

        $withheld = self::sectionsTotal($contact) - self::sectionsShown($link, $contact);

        if ($withheld === 1) {
            $held[] = __('One section');
        }

        if ($withheld > 1) {
            $held[] = __(':count sections', ['count' => $withheld]);
        }

        if (! $link->includes_sensitive_fields) {
            $held[] = __('The fields you marked sensitive');
        }

        $held[] = __('Anything attached as a file');
        $held[] = __('Who changed what and when, and everything technical');

        return implode(' · ', $held);
    }

    /**
     * When it stops working, or when it stopped. The three states read as three
     * different sentences rather than one date somebody has to interpret.
     */
    public static function endsLine(ShareLink $link): string
    {
        if ($link->revoked_at !== null) {
            return __('Revoked :when', ['when' => $link->revoked_at->isoFormat('LL')]);
        }

        if ($link->expires_at === null) {
            return __('Never, until you revoke it');
        }

        if ($link->expires_at->isPast()) {
            return __('Expired :when', ['when' => $link->expires_at->isoFormat('LL')]);
        }

        return __(':date, :relative', [
            'date' => $link->expires_at->isoFormat('LL'),
            'relative' => $link->expires_at->diffForHumans(),
        ]);
    }

    public static function sectionsShown(ShareLink $link, Contact $contact): int
    {
        $excluded = $link->excluded_section_ids;

        return $contact->templateVersion->assembly
            ->reject(fn (TemplateVersionSection $row): bool => in_array($row->id, $excluded, true))
            ->count();
    }

    public static function sectionsTotal(Contact $contact): int
    {
        return $contact->templateVersion->assembly->count();
    }
}
