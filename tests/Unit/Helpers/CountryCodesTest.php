<?php

declare(strict_types=1);

namespace Tests\Unit\Helpers;

use App\Enums\ValueListEnum;
use App\Helpers\CountryCodes;
use App\Models\Account;
use App\Models\ValueList;
use App\Models\ValueListItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CountryCodesTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_lists_every_country_named_and_sorted_in_a_language(): void
    {
        $countries = CountryCodes::all('fr_FR');
        $names = array_values($countries);

        $this->assertCount(256, $countries);
        $this->assertEquals('Allemagne', $countries['DE']);
        $this->assertEquals('Kosovo', $countries['XK']);
        $this->assertLessThan(array_search('Espagne', $names, true), array_search('Égypte', $names, true));
        $this->assertGreaterThan(array_search('Dominique', $names, true), array_search('Égypte', $names, true));
    }

    #[Test]
    public function it_names_one_country_in_a_language(): void
    {
        $this->assertEquals('Allemagne', CountryCodes::nameOf(code: 'de', locale: 'fr_FR'));
        $this->assertNull(CountryCodes::nameOf(code: 'QQ', locale: 'en'));
    }

    #[Test]
    public function it_reads_the_code_of_a_country_somebody_added(): void
    {
        $account = Account::factory()->create();
        $this->country(account: $account, attributes: ['label' => 'Japan', 'country_code' => 'JP']);

        $this->assertEquals('JP', new CountryCodes()->codeFor(accountId: $account->id, country: ' japan '));
    }

    #[Test]
    public function it_reads_a_renamed_supplied_country_by_its_new_label_and_by_its_supplied_names(): void
    {
        $account = Account::factory()->create();
        $this->country(account: $account, attributes: ['key' => 'de', 'label' => 'Deutschland']);
        $codes = new CountryCodes;

        $this->assertEquals('DE', $codes->codeFor(accountId: $account->id, country: 'Deutschland'));
        $this->assertEquals('DE', $codes->codeFor(accountId: $account->id, country: 'Germany'));
        $this->assertEquals('DE', $codes->codeFor(accountId: $account->id, country: 'Allemagne'));
    }

    #[Test]
    public function it_reads_a_code_written_as_the_country(): void
    {
        $account = Account::factory()->create();

        $this->assertEquals('FR', new CountryCodes()->codeFor(accountId: $account->id, country: 'fr'));
    }

    #[Test]
    public function it_finds_no_code_for_a_country_nobody_knows(): void
    {
        $account = Account::factory()->create();

        $this->assertNull(new CountryCodes()->codeFor(accountId: $account->id, country: 'Narnia'));
        $this->assertNull(new CountryCodes()->codeFor(accountId: $account->id, country: 'XX'));
    }

    #[Test]
    public function it_ignores_the_countries_of_another_account(): void
    {
        $account = Account::factory()->create();
        $this->country(account: Account::factory()->create(), attributes: ['label' => 'Japan', 'country_code' => 'JP']);

        $this->assertNull(new CountryCodes()->codeFor(accountId: $account->id, country: 'Japan'));
    }

    /** @param  array<string, mixed>  $attributes */
    private function country(Account $account, array $attributes): ValueListItem
    {
        $list = ValueList::factory()->create(['account_id' => $account->id, 'key' => ValueListEnum::Countries->value]);

        return ValueListItem::factory()->create([
            'account_id' => $account->id,
            'value_list_id' => $list->id,
            ...$attributes,
        ]);
    }
}
