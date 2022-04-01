<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\User;
use App\Models\Gender;
use App\Models\Module;
use App\Models\Account;
use App\Models\Emotion;
use App\Models\Pronoun;
use App\Models\Currency;
use App\Models\Template;
use App\Models\GroupType;
use App\Models\AddressType;
use App\Models\PetCategory;
use App\Models\RelationshipGroupType;
use App\Models\ContactInformationType;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class AccountTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_many_users()
    {
        $account = Account::factory()->create();
        User::factory()->count(2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->users()->exists());
    }

    /** @test */
    public function it_has_many_templates()
    {
        $account = Account::factory()->create();
        Template::factory(2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->templates()->exists());
    }

    /** @test */
    public function it_has_many_modules()
    {
        $account = Account::factory()->create();
        Module::factory(2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->modules()->exists());
    }

    /** @test */
    public function it_has_many_group_types()
    {
        $account = Account::factory()->create();
        GroupType::factory(2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->groupTypes()->exists());
    }

    /** @test */
    public function it_has_many_relationship_group_types()
    {
        $account = Account::factory()->create();
        RelationshipGroupType::factory(2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->relationshipGroupTypes()->exists());
    }

    /** @test */
    public function it_has_many_genders()
    {
        $account = Account::factory()->create();
        Gender::factory(2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->genders()->exists());
    }

    /** @test */
    public function it_has_many_pronouns()
    {
        $account = Account::factory()->create();
        Pronoun::factory(2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->pronouns()->exists());
    }

    /** @test */
    public function it_has_many_contact_information_types()
    {
        $account = Account::factory()->create();
        ContactInformationType::factory(2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->contactInformationTypes()->exists());
    }

    /** @test */
    public function it_has_many_address_types()
    {
        $account = Account::factory()->create();
        AddressType::factory(2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->addressTypes()->exists());
    }

    /** @test */
    public function it_has_many_pet_categories()
    {
        $account = Account::factory()->create();
        PetCategory::factory(2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->petCategories()->exists());
    }

    /** @test */
    public function it_has_many_emotions()
    {
        $account = Account::factory()->create();
        Emotion::factory(2)->create([
            'account_id' => $account->id,
        ]);

        $this->assertTrue($account->emotions()->exists());
    }

    /** @test */
    public function it_belongs_to_many_currencies(): void
    {
        $account = Account::factory()->create();
        $currency = Currency::factory()->create();

        $account->currencies()->sync([$currency->id => ['active' => true]]);

        $this->assertTrue($account->currencies()->exists());
    }
}
