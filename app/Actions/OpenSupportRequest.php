<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\SupportCategoryEnum;
use App\Enums\SupportMessageKindEnum;
use App\Enums\SupportRequestStatusEnum;
use App\Enums\UserActionEnum;
use App\Helpers\SupportAttachmentWriter;
use App\Helpers\TextSanitizer;
use App\Jobs\LogUserAction;
use App\Models\SupportMessage;
use App\Models\SupportRequest;
use App\Models\SupportStatusChange;
use App\Models\User;
use App\Notifications\SupportRequestReceived;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

/**
 * Ask the administrators of the instance for help: open a request with its first message and the
 * files sent with it, put it in their queue, and send the user a copy of what they wrote.
 */
class OpenSupportRequest
{
    private SupportRequest $supportRequest;

    private string $cleanSubject;

    private string $cleanBody;

    /** @param  list<UploadedFile>  $files */
    public function __construct(
        private readonly User $user,
        private readonly SupportCategoryEnum $category,
        private readonly string $subject,
        private readonly string $body,
        private readonly array $files = [],
    ) {}

    public function execute(): SupportRequest
    {
        $this->sanitize();
        $this->create();
        $this->log();
        $this->notify();

        return $this->supportRequest;
    }

    private function sanitize(): void
    {
        $this->cleanSubject = TextSanitizer::plainText($this->subject);
        $this->cleanBody = TextSanitizer::plainText($this->body);
    }

    private function create(): void
    {
        DB::transaction(function (): void {
            $this->supportRequest = SupportRequest::query()->create([
                'number' => $this->unusedNumber(),
                'account_id' => $this->user->account_id,
                'user_id' => $this->user->id,
                'category' => $this->category,
                'subject' => $this->cleanSubject,
                'status' => SupportRequestStatusEnum::ToHandle,
                'application_version' => (string) config('monica.version'),
                'locale' => $this->user->locale ?? app()->getLocale(),
                'last_activity_at' => now(),
            ]);

            $message = SupportMessage::query()->create([
                'support_request_id' => $this->supportRequest->id,
                'author_user_id' => $this->user->id,
                'author_name' => $this->user->name,
                'kind' => SupportMessageKindEnum::Message,
                'body' => $this->cleanBody,
            ]);

            foreach ($this->files as $file) {
                SupportAttachmentWriter::store($message, $this->user->account_id, $file);
            }

            SupportStatusChange::query()->create([
                'support_request_id' => $this->supportRequest->id,
                'from_status' => null,
                'to_status' => SupportRequestStatusEnum::ToHandle,
                'user_id' => $this->user->id,
                'user_name' => $this->user->name,
            ]);
        });
    }

    private function unusedNumber(): int
    {
        do {
            $number = random_int(100000, 999999);
        } while (SupportRequest::query()->where('number', $number)->exists());

        return $number;
    }

    private function log(): void
    {
        LogUserAction::dispatch(
            account: $this->user->account,
            user: $this->user,
            action: UserActionEnum::SupportRequestOpened,
            parameters: ['subject' => $this->cleanSubject],
            loggable: $this->supportRequest,
        );
    }

    private function notify(): void
    {
        $this->user->notify(new SupportRequestReceived(
            reference: $this->supportRequest->reference,
            subject: $this->cleanSubject,
            body: $this->cleanBody,
            url: route('support.requests.show', $this->supportRequest),
        ));
    }
}
