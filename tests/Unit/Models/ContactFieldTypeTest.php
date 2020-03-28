<?php

namespace Tests\Unit\Models;

use App\Models\Account\Account;
use App\Models\Contact\ContactFieldType;
use App\Models\Contact\Conversation;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\FeatureTestCase;

class ContactFieldTypeTest extends FeatureTestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_many_conversations()
    {
        $contactFieldType = factory(ContactFieldType::class)->create([]);
        $conversation = factory(Conversation::class, 3)->create([
            'account_id' => $contactFieldType->account_id,
            'contact_field_type_id' => $contactFieldType->id,
        ]);

        $this->assertTrue($contactFieldType->conversations()->exists());
    }

    /** @test */
    public function it_belongs_to_an_account()
    {
        $account = factory(Account::class)->create([]);
        $contactFieldType = factory(ContactFieldType::class)->create([]);
        $conversation = factory(Conversation::class, 3)->create([
            'account_id' => $account->id,
            'contact_field_type_id' => $contactFieldType->id,
        ]);

        $this->assertTrue($contactFieldType->account()->exists());
    }
}
