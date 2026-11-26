<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateContactListColumns;
use App\Enums\ContactListColumnSourceEnum;
use App\Enums\FieldTypeEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\ContactListColumn;
use App\Models\Field;
use App\Models\Section;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateContactListColumnsTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_replaces_the_columns_of_the_vault_in_the_order_given(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id, 'name' => 'Dunder Mifflin']);
        $work = $this->contactTemplateCarrying($user, 'Professional', ['Work' => ['company']])['Work'];
        ContactListColumn::factory()->create(['vault_id' => $vault->id, 'source' => ContactListColumnSourceEnum::Nickname]);

        $returned = new UpdateContactListColumns(
            user: $user,
            vault: $vault,
            columns: [
                ['source' => 'field', 'section' => $work->uuid, 'field_key' => 'company', 'heading' => '  Employer ', 'is_visible' => true],
                ['source' => 'email', 'section' => null, 'field_key' => null, 'heading' => '', 'is_visible' => false],
            ],
        )->execute();

        $this->assertInstanceOf(Vault::class, $returned);
        $this->assertDatabaseCount('contact_list_columns', 2);
        $this->assertDatabaseHas('contact_list_columns', [
            'vault_id' => $vault->id,
            'account_id' => $user->account_id,
            'source' => 'field',
            'section_id' => $work->id,
            'field_key' => 'company',
            'heading' => 'Employer',
            'position' => 1,
            'is_visible' => true,
        ]);
        $this->assertDatabaseHas('contact_list_columns', [
            'vault_id' => $vault->id,
            'source' => 'email',
            'heading' => null,
            'position' => 2,
            'is_visible' => false,
        ]);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactListColumnsUpdate
                && $job->vault->id === $vault->id
                && $job->parameters === ['name' => 'Dunder Mifflin'],
        );
    }

    #[Test]
    public function it_refuses_to_show_the_same_column_twice(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);

        new UpdateContactListColumns(user: $user, vault: $vault, columns: [
            ['source' => 'phone', 'section' => null, 'field_key' => null, 'heading' => null, 'is_visible' => true],
            ['source' => 'phone', 'section' => null, 'field_key' => null, 'heading' => 'Cell', 'is_visible' => true],
        ])->execute();
    }

    #[Test]
    public function it_refuses_to_add_a_field_no_template_carries(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $section = Section::factory()->create(['account_id' => $user->account_id]);

        $this->expectException(InvalidArgumentException::class);

        new UpdateContactListColumns(user: $user, vault: $vault, columns: [
            ['source' => 'field', 'section' => $section->uuid, 'field_key' => 'sales_region', 'heading' => null, 'is_visible' => true],
        ])->execute();
    }

    #[Test]
    public function it_names_the_shared_column_when_the_field_is_an_email(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $section = $this->contactTemplateCarrying($user, 'Professional', ['Work' => ['work_email']])['Work'];
        Field::query()->where('key', 'work_email')->update(['type' => FieldTypeEnum::Email]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This field is shown by the Email column. Choose that column instead.');

        new UpdateContactListColumns(user: $user, vault: $vault, columns: [
            ['source' => 'field', 'section' => $section->uuid, 'field_key' => 'work_email', 'heading' => null, 'is_visible' => true],
        ])->execute();
    }

    #[Test]
    public function it_names_the_shared_column_when_the_field_is_a_phone_number(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $section = $this->contactTemplateCarrying($user, 'Professional', ['Work' => ['work_phone']])['Work'];
        Field::query()->where('key', 'work_phone')->update(['type' => FieldTypeEnum::Phone]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('This field is shown by the Phone number column. Choose that column instead.');

        new UpdateContactListColumns(user: $user, vault: $vault, columns: [
            ['source' => 'field', 'section' => $section->uuid, 'field_key' => 'work_phone', 'heading' => null, 'is_visible' => true],
        ])->execute();
    }

    #[Test]
    public function it_refuses_a_sensitive_field_with_its_own_reason(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $section = $this->contactTemplateCarrying($user, 'Professional', ['Work' => ['salary']])['Work'];
        Field::query()->where('key', 'salary')->update(['is_sensitive' => true]);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('A sensitive field cannot be shown in the list of people.');

        new UpdateContactListColumns(user: $user, vault: $vault, columns: [
            ['source' => 'field', 'section' => $section->uuid, 'field_key' => 'salary', 'heading' => null, 'is_visible' => true],
        ])->execute();
    }

    #[Test]
    public function it_keeps_a_column_whose_field_no_template_carries_any_more(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $section = Section::factory()->create(['account_id' => $user->account_id]);
        ContactListColumn::factory()->field($section, 'sales_region')->create(['vault_id' => $vault->id]);

        new UpdateContactListColumns(user: $user, vault: $vault, columns: [
            ['source' => 'nickname', 'section' => null, 'field_key' => null, 'heading' => null, 'is_visible' => true],
            ['source' => 'field', 'section' => $section->uuid, 'field_key' => 'sales_region', 'heading' => 'Territory', 'is_visible' => true],
        ])->execute();

        $this->assertDatabaseHas('contact_list_columns', [
            'vault_id' => $vault->id,
            'section_id' => $section->id,
            'field_key' => 'sales_region',
            'heading' => 'Territory',
            'position' => 2,
        ]);
    }

    #[Test]
    public function it_refuses_a_vault_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateContactListColumns(user: $user, vault: $vault, columns: [])->execute();
    }

    #[Test]
    public function it_refuses_a_section_of_another_account(): void
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $section = Section::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new UpdateContactListColumns(user: $user, vault: $vault, columns: [
            ['source' => 'field', 'section' => $section->uuid, 'field_key' => 'company', 'heading' => null, 'is_visible' => true],
        ])->execute();
    }
}
