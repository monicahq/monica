<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ExportContactMarkdown;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExportContactMarkdownTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_the_contact_out_and_records_the_export(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $contact = Contact::factory()->create(['vault_id' => $vault->id, 'account_id' => $user->account_id, 'first_name' => 'Jim', 'last_name' => 'Halpert']);

        $document = new ExportContactMarkdown(user: $user, contact: $contact)->execute();

        $this->assertStringStartsWith('# Jim Halpert', $document);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::ContactExportedAsMarkdown
            && $job->parameters === ['name' => 'Jim Halpert']
            && $job->isAbout($contact));
    }

    #[Test]
    public function it_refuses_a_contact_in_another_account(): void
    {
        Queue::fake();

        $user = User::factory()->create();
        $contact = Contact::factory()->create();

        $this->expectException(ModelNotFoundException::class);

        new ExportContactMarkdown(user: $user, contact: $contact)->execute();
    }
}
