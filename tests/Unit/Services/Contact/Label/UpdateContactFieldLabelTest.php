<?php

namespace Tests\Unit\Services\Contact\Label;

use Tests\TestCase;
use App\Models\Account\Account;
use App\Models\Contact\ContactField;
use App\Models\Contact\ContactFieldLabel;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use App\Services\Contact\Label\UpdateContactFieldLabels;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateContactFieldLabelTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_creates_contact_field_labels()
    {
        $account = factory(Account::class)->create([]);
        $contactField = factory(ContactField::class)->create([
            'account_id' => $account->id,
        ]);

        app(UpdateContactFieldLabels::class)->execute([
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
            'labels' => ['HOME'],
        ]);

        $this->assertDatabaseHas('contact_field_labels', [
            'account_id' => $account->id,
            'label_i18n' => 'home',
        ]);

        $contactFieldLabel = ContactFieldLabel::where([
            'account_id' => $account->id,
            'label_i18n' => 'home',
        ])->first();
        $this->assertDatabaseHas('contact_field_contact_field_label', [
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
            'contact_field_label_id' => $contactFieldLabel->id,
        ]);
    }

    /** @test */
    public function it_creates_personal_contact_field_labels()
    {
        $account = factory(Account::class)->create([]);
        $contactField = factory(ContactField::class)->create([
            'account_id' => $account->id,
        ]);

        app(UpdateContactFieldLabels::class)->execute([
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
            'labels' => ['Family'],
        ]);

        $this->assertDatabaseHas('contact_field_labels', [
            'account_id' => $account->id,
            'label' => 'Family',
        ]);

        $contactFieldLabel = ContactFieldLabel::where([
            'account_id' => $account->id,
            'label' => 'Family',
        ])->first();
        $this->assertDatabaseHas('contact_field_contact_field_label', [
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
            'contact_field_label_id' => $contactFieldLabel->id,
        ]);
    }

    /** @test */
    public function it_creates_contact_field_multiple_labels()
    {
        $account = factory(Account::class)->create([]);
        $contactField = factory(ContactField::class)->create([
            'account_id' => $account->id,
        ]);

        app(UpdateContactFieldLabels::class)->execute([
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
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
        $this->assertDatabaseHas('contact_field_contact_field_label', [
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
            'contact_field_label_id' => $homeLabel->id,
        ]);
        $mainLabel = ContactFieldLabel::where([
            'account_id' => $account->id,
            'label_i18n' => 'main',
        ])->first();
        $this->assertDatabaseHas('contact_field_contact_field_label', [
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
            'contact_field_label_id' => $mainLabel->id,
        ]);
        $cellLabel = ContactFieldLabel::where([
            'account_id' => $account->id,
            'label_i18n' => 'cell',
        ])->first();
        $this->assertDatabaseHas('contact_field_contact_field_label', [
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
            'contact_field_label_id' => $cellLabel->id,
        ]);
    }

    /** @test */
    public function it_adds_contact_field_labels()
    {
        $account = factory(Account::class)->create([]);
        $contactField = factory(ContactField::class)->create([
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
        $contactField->labels()->sync([$homeLabel->id => ['account_id' => $account->id]]);

        $this->assertDatabaseHas('contact_field_contact_field_label', [
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
            'contact_field_label_id' => $homeLabel->id,
        ]);

        app(UpdateContactFieldLabels::class)->execute([
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
            'labels' => ['home', 'cell'],
        ]);

        $this->assertDatabaseHas('contact_field_contact_field_label', [
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
            'contact_field_label_id' => $homeLabel->id,
        ]);
        $this->assertDatabaseHas('contact_field_contact_field_label', [
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
            'contact_field_label_id' => $cellLabel->id,
        ]);
    }

    /** @test */
    public function it_removes_contact_field_labels()
    {
        $account = factory(Account::class)->create([]);
        $contactField = factory(ContactField::class)->create([
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
        $contactField->labels()->sync([$homeLabel->id => ['account_id' => $account->id]]);

        $this->assertDatabaseHas('contact_field_contact_field_label', [
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
            'contact_field_label_id' => $homeLabel->id,
        ]);

        app(UpdateContactFieldLabels::class)->execute([
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
            'labels' => ['cell'],
        ]);

        $this->assertDatabaseMissing('contact_field_contact_field_label', [
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
            'contact_field_label_id' => $homeLabel->id,
        ]);
        $this->assertDatabaseHas('contact_field_contact_field_label', [
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
            'contact_field_label_id' => $cellLabel->id,
        ]);
    }

    /** @test */
    public function it_throws_an_exception_if_account_doesnt_exist()
    {
        $contactField = factory(ContactField::class)->create();

        $this->expectException(ValidationException::class);

        app(UpdateContactFieldLabels::class)->execute([
            'account_id' => -1,
            'contact_field_id' => $contactField->id,
            'labels' => ['cell'],
        ]);
    }

    /** @test */
    public function it_throws_an_exception_if_contact_field_doesnt_exist()
    {
        $account = factory(Account::class)->create([]);

        $this->expectException(ValidationException::class);

        app(UpdateContactFieldLabels::class)->execute([
            'account_id' => $account->id,
            'contact_field_id' => -1,
            'labels' => ['cell'],
        ]);
    }

    /** @test */
    public function it_throws_an_exception_if_contact_field_is_wrong_account()
    {
        $account = factory(Account::class)->create([]);
        $contactField = factory(ContactField::class)->create();

        $this->expectException(ModelNotFoundException::class);

        app(UpdateContactFieldLabels::class)->execute([
            'account_id' => $account->id,
            'contact_field_id' => $contactField->id,
            'labels' => ['cell'],
        ]);
    }
}
