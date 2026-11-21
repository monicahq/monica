<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Enums\CountryEnum;
use App\Enums\ValueListEnum;
use App\Models\ValueListItem;
use CommerceGuys\Addressing\Country\CountryRepository;

/**
 * Finds the ISO 3166 code of the country an address stores as text. Registered as a scoped
 * instance, so each account's list is read once per request or job.
 */
class CountryCodes
{
    /** @var array<int, array<string, string>> */
    private array $codesByAccount = [];

    /** @var array<string, string>|null */
    private ?array $suppliedCodes = null;

    /** @return list<string> */
    public static function known(): array
    {
        return once(fn (): array => array_map(strval(...), array_keys(new CountryRepository()->getList())));
    }

    public function codeFor(int $accountId, string $country): ?string
    {
        $text = mb_strtolower(mb_trim($country));

        if ($text === '') {
            return null;
        }

        $code = $this->accountCodes($accountId)[$text] ?? $this->suppliedCodes()[$text] ?? null;

        if ($code !== null) {
            return $code;
        }

        $upper = mb_strtoupper($text);

        return in_array($upper, self::known(), true) ? $upper : null;
    }

    /**
     * Disabled and deleted items still name the countries of the addresses saved under them. They
     * are read first, so a live item of the same label wins.
     *
     * @return array<string, string>
     */
    private function accountCodes(int $accountId): array
    {
        if (isset($this->codesByAccount[$accountId])) {
            return $this->codesByAccount[$accountId];
        }

        $known = self::known();
        $codes = [];

        $items = ValueListItem::withTrashed()
            ->where('account_id', $accountId)
            ->whereRelation('valueList', 'key', ValueListEnum::Countries->value)
            ->orderByRaw('deleted_at is null')
            ->orderBy('is_active')
            ->get();

        foreach ($items as $item) {
            $code = mb_strtoupper((string) ($item->country_code ?? $item->key));

            if (in_array($code, $known, true)) {
                $codes[mb_strtolower(mb_trim($item->label))] = $code;
            }
        }

        return $this->codesByAccount[$accountId] = $codes;
    }

    /**
     * The supplied countries by their name in every shipped language, for an address saved in
     * another interface language or before its country was renamed.
     *
     * @return array<string, string>
     */
    private function suppliedCodes(): array
    {
        if ($this->suppliedCodes !== null) {
            return $this->suppliedCodes;
        }

        $codes = [];

        foreach (CountryEnum::cases() as $country) {
            $code = mb_strtoupper($country->value);
            $codes[mb_strtolower($country->label())] = $code;

            foreach (AvailableLocales::codes() as $locale) {
                $codes[mb_strtolower(mb_trim(__($country->label(), [], $locale)))] = $code;
            }
        }

        return $this->suppliedCodes = $codes;
    }
}
