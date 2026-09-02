<?php

declare(strict_types=1);

namespace Tests;

use App\Models\Account;
use App\Models\Contact;
use App\Models\Field;
use App\Models\FieldValue;
use App\Models\Reminder;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Put a user in an account, for a test that builds the account first and
     * the person it belongs to afterwards.
     *
     * An account holds exactly one user, which the unique index on
     * `users.account_id` enforces, so the account has to be one nobody is in
     * yet. Nothing else is passed: an account carries no role, and everything it
     * holds belongs to the one person in it. The one capacity that exists is
     * instance wide and granted separately, with
     * `Administrator::factory()->create(['user_id' => $user->id])`.
     */
    protected function assignUserToAccount(User $user, Account $account): User
    {
        $user->account_id = $account->id;
        $user->save();

        return $user;
    }

    /**
     * A reminder rule about one contact, reading one date on it.
     *
     * It is here rather than repeated in every test that has to prove a rule
     * went quiet or came back, because six actions now silence or revive one and
     * each of them needs a rule to act on. What it builds is deliberately the
     * whole chain a rule needs: a contact, a date field in the same account, a
     * value holding a date, and the rule reading it.
     */
    protected function reminderAbout(User $user, ?Contact $contact = null, ?Field $field = null): Reminder
    {
        $contact ??= Contact::factory()->create(['account_id' => $user->account_id]);
        $field ??= Field::factory()->recurringDate()->create(['account_id' => $user->account_id]);

        $value = FieldValue::factory()->create([
            'valuable_type' => Contact::class,
            'valuable_id' => $contact->id,
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'field_id' => $field->id,
            'field_key' => $field->key,
            'value_string' => null,
            'value_date' => '1984-02-03',
        ]);

        return Reminder::factory()->for($value, 'fieldValue')->create([
            'account_id' => $contact->account_id,
            'vault_id' => $contact->vault_id,
            'remindable_type' => Contact::class,
            'remindable_id' => $contact->id,
            'field_id' => $field->id,
            'created_by_user_id' => $user->id,
        ]);
    }
}
