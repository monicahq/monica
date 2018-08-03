<?php

namespace Tests\Unit;

use Carbon\Carbon;
use Tests\FeatureTestCase;
use App\Models\Account\Account;
use Illuminate\Support\Facades\DB;
use App\Models\Contact\Conversation;
use App\Models\Contact\ContactFieldType;
use App\Models\Relationship\RelationshipType;
use App\Models\Relationship\RelationshipTypeGroup;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class ContactFieldTypeTest extends FeatureTestCase
{
    use DatabaseTransactions;

    public function test_it_has_many_conversations()
    {
        $contactFieldType = factory(ContactFieldType::class)->create([]);
        $conversation = factory(Conversation::class, 3)->create([
            'account_id' => $contactFieldType->account->id,
            'contact_field_type_id' => $contactFieldType->id,
        ]);

        $this->assertTrue($contactFieldType->conversations()->exists());
    }

    public function test_it_belongs_to_an_account()
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
