<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\OpenSupportRequest;
use App\Enums\SupportCategoryEnum;
use App\Enums\SupportMessageKindEnum;
use App\Enums\SupportRequestStatusEnum;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Account;
use App\Models\SupportAttachment;
use App\Models\SupportRequest;
use App\Models\User;
use App\Notifications\SupportRequestReceived;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OpenSupportRequestTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_opens_a_request_in_the_queue_of_the_administrators(): void
    {
        Queue::fake();
        Notification::fake();
        Storage::fake();
        config(['monica.version' => '5.0.0-beta.4']);

        $user = User::factory()->create(['first_name' => 'Michael', 'last_name' => 'Scott', 'locale' => 'fr_FR']);

        $supportRequest = new OpenSupportRequest(
            user: $user,
            category: SupportCategoryEnum::Billing,
            subject: '  October invoice shows the <b>wrong</b> company name ',
            body: 'The invoice still says Dunder Mifflin Paper Company.',
        )->execute();

        $this->assertInstanceOf(SupportRequest::class, $supportRequest);
        $this->assertDatabaseHas('support_requests', [
            'id' => $supportRequest->id,
            'account_id' => $user->account_id,
            'user_id' => $user->id,
            'category' => 'billing',
            'subject' => 'October invoice shows the wrong company name',
            'status' => 'to_handle',
            'application_version' => '5.0.0-beta.4',
            'locale' => 'fr_FR',
        ]);
        $this->assertDatabaseHas('support_messages', [
            'support_request_id' => $supportRequest->id,
            'author_user_id' => $user->id,
            'author_name' => 'Michael Scott',
            'kind' => SupportMessageKindEnum::Message->value,
            'body' => 'The invoice still says Dunder Mifflin Paper Company.',
        ]);
        $this->assertDatabaseHas('support_status_changes', [
            'support_request_id' => $supportRequest->id,
            'from_status' => null,
            'to_status' => SupportRequestStatusEnum::ToHandle->value,
            'user_name' => 'Michael Scott',
        ]);
        $this->assertGreaterThanOrEqual(100000, $supportRequest->number);
        $this->assertLessThanOrEqual(999999, $supportRequest->number);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::SupportRequestOpened);
        Notification::assertSentTo($user, SupportRequestReceived::class, fn (SupportRequestReceived $notification): bool => $notification->reference === $supportRequest->reference);
    }

    #[Test]
    public function it_records_the_language_shown_when_the_user_never_picked_one(): void
    {
        Queue::fake();
        Notification::fake();
        app()->setLocale('de_DE');

        $user = User::factory()->create(['locale' => null]);

        $supportRequest = new OpenSupportRequest(
            user: $user,
            category: SupportCategoryEnum::Feature,
            subject: 'Sort people by last contacted',
            body: 'Sorting by the date I last talked to someone would help.',
        )->execute();

        $this->assertEquals('de_DE', $supportRequest->locale);
    }

    #[Test]
    public function it_keeps_the_files_outside_the_storage_the_account_is_allowed(): void
    {
        Queue::fake();
        Notification::fake();
        Storage::fake();

        $account = Account::factory()->create(['storage_used_in_bytes' => 104857600, 'storage_limit_in_bytes' => 104857600]);
        $user = User::factory()->create(['account_id' => $account->id]);

        $supportRequest = new OpenSupportRequest(
            user: $user,
            category: SupportCategoryEnum::Technical,
            subject: 'Import keeps timing out',
            body: 'Here is the screen I get every time.',
            files: [UploadedFile::fake()->image('import-error.png')],
        )->execute();

        $attachment = SupportAttachment::query()->firstOrFail();

        $this->assertEquals($supportRequest->messages()->first()->id, $attachment->support_message_id);
        $this->assertEquals('import-error.png', $attachment->original_name);
        $this->assertStringStartsWith("support/{$account->id}/", $attachment->path);
        Storage::assertExists($attachment->path);
        $this->assertEquals(104857600, $account->refresh()->storage_used_in_bytes);
    }
}
