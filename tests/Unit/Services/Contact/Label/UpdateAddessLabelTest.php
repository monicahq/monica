<?php

namespace Tests\Unit\Services\Contact\Label;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\Address;
use App\Models\Contact\ContactFieldLabel;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Label\UpdateAddressLabels;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateAddessLabelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_contact_field_labels()
    {
        $account = factory(Account::class)->create([]);
        $address = factory(Address::class)->create([
            'account_id' => $account->id,
        ]);

        app(UpdateAddressLabels::class)->execute([
            'account_id' => $account->id,
            'address_id' => $address->id,
            'labels' => ['home'],
        ]);

        $this->assertDatabaseHas('contact_field_labels', [
            'account_id' => $account->id,
            'label_i18n' => 'home',
        ]);

        $contactFieldLabel = ContactFieldLabel::where([
            'account_id' => $account->id,
            'label_i18n' => 'home',
        ])->first();
        $this->assertDatabaseHas('address_contact_field_label', [
            'account_id' => $account->id,
            'address_id' => $address->id,
            'contact_field_label_id' => $contactFieldLabel->id,
        ]);
    }

    /** @test */
    public function it_creates_contact_field_multiple_labels()
    {
        $account = factory(Account::class)->create([]);
        $address = factory(Address::class)->create([
            'account_id' => $account->id,
        ]);

        app(UpdateAddressLabels::class)->execute([
            'account_id' => $account->id,
            'address_id' => $address->id,
            'labels' => ['home', 'main', 'cell'],
        ]);

        $this->assertDatabaseHas('contact_field_labels', [
            'account_id' => $account->id,
            'label_i18n' => 'home',
        ]);
        $this->assertDatabaseHas('contact_field_labels', [
            'account_id' => $account->id,
            'label_i18n' => 'main',
        ]);
        $this->assertDatabaseHas('contact_field_labels', [
            'account_id' => $account->id,
            'label_i18n' => 'cell',
        ]);

        $homeLabel = ContactFieldLabel::where([
            'account_id' => $account->id,
            'label_i18n' => 'home',
        ])->first();
        $this->assertDatabaseHas('address_contact_field_label', [
            'account_id' => $account->id,
            'address_id' => $address->id,
            'contact_field_label_id' => $homeLabel->id,
        ]);
        $mainLabel = ContactFieldLabel::where([
            'account_id' => $account->id,
            'label_i18n' => 'main',
        ])->first();
        $this->assertDatabaseHas('address_contact_field_label', [
            'account_id' => $account->id,
            'address_id' => $address->id,
            'contact_field_label_id' => $mainLabel->id,
        ]);
        $cellLabel = ContactFieldLabel::where([
            'account_id' => $account->id,
            'label_i18n' => 'cell',
        ])->first();
        $this->assertDatabaseHas('address_contact_field_label', [
            'account_id' => $account->id,
            'address_id' => $address->id,
            'contact_field_label_id' => $cellLabel->id,
        ]);
    }

    /** @test */
    public function it_adds_contact_field_labels()
    {
        $account = factory(Account::class)->create([]);
        $address = factory(Address::class)->create([
            'account_id' => $account->id,
        ]);
        $homeLabel = factory(ContactFieldLabel::class)->create([
            'account_id' => $account->id,
            'label_i18n' => 'home',
        ]);
        $cellLabel = factory(ContactFieldLabel::class)->create([
            'account_id' => $account->id,
            'label_i18n' => 'cell',
        ]);
        $address->labels()->sync([$homeLabel->id => ['account_id' => $account->id]]);

        $this->assertDatabaseHas('address_contact_field_label', [
            'account_id' => $account->id,
            'address_id' => $address->id,
            'contact_field_label_id' => $homeLabel->id,
        ]);

        app(UpdateAddressLabels::class)->execute([
            'account_id' => $account->id,
            'address_id' => $address->id,
            'labels' => ['home', 'cell'],
        ]);

        $this->assertDatabaseHas('address_contact_field_label', [
            'account_id' => $account->id,
            'address_id' => $address->id,
            'contact_field_label_id' => $homeLabel->id,
        ]);
        $this->assertDatabaseHas('address_contact_field_label', [
            'account_id' => $account->id,
            'address_id' => $address->id,
            'contact_field_label_id' => $cellLabel->id,
        ]);
    }

    /** @test */
    public function it_removes_contact_field_labels()
    {
        $account = factory(Account::class)->create([]);
        $address = factory(Address::class)->create([
            'account_id' => $account->id,
        ]);
        $homeLabel = factory(ContactFieldLabel::class)->create([
            'account_id' => $account->id,
            'label_i18n' => 'home',
        ]);
        $cellLabel = factory(ContactFieldLabel::class)->create([
            'account_id' => $account->id,
            'label_i18n' => 'cell',
        ]);
        $address->labels()->sync([$homeLabel->id => ['account_id' => $account->id]]);

        $this->assertDatabaseHas('address_contact_field_label', [
            'account_id' => $account->id,
            'address_id' => $address->id,
            'contact_field_label_id' => $homeLabel->id,
        ]);

        app(UpdateAddressLabels::class)->execute([
            'account_id' => $account->id,
            'address_id' => $address->id,
            'labels' => ['cell'],
        ]);

        $this->assertDatabaseMissing('address_contact_field_label', [
            'account_id' => $account->id,
            'address_id' => $address->id,
            'contact_field_label_id' => $homeLabel->id,
        ]);
        $this->assertDatabaseHas('address_contact_field_label', [
            'account_id' => $account->id,
            'address_id' => $address->id,
            'contact_field_label_id' => $cellLabel->id,
        ]);
    }

    /** @test */
    public function it_throws_an_exception_if_account_doesnt_exist()
    {
        $address = factory(Address::class)->create();

        $this->expectException(ValidationException::class);

        app(UpdateAddressLabels::class)->execute([
            'account_id' => -1,
            'address_id' => $address->id,
            'labels' => ['cell'],
        ]);
    }

    /** @test */
    public function it_throws_an_exception_if_contact_field_doesnt_exist()
    {
        $account = factory(Account::class)->create([]);

        $this->expectException(ValidationException::class);

        app(UpdateAddressLabels::class)->execute([
            'account_id' => $account->id,
            'address_id' => -1,
            'labels' => ['cell'],
        ]);
    }

    /** @test */
    public function it_throws_an_exception_if_contact_field_is_wrong_account()
    {
        $account = factory(Account::class)->create([]);
        $address = factory(Address::class)->create();

        $this->expectException(ModelNotFoundException::class);

        app(UpdateAddressLabels::class)->execute([
            'account_id' => $account->id,
            'address_id' => $address->id,
            'labels' => ['cell'],
        ]);
    }
}
