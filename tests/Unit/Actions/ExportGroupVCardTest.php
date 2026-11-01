<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\ExportGroupVCard;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Contact;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ExportGroupVCardTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_writes_the_group_card_then_the_card_of_every_current_member(): void
    {
        Queue::fake();
        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => $vault->id, 'name' => 'Finer things club']);
        $pam = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Pam', 'last_name' => 'Beesly']);
        $toby = Contact::factory()->create(['account_id' => $user->account_id, 'vault_id' => $vault->id, 'first_name' => 'Toby', 'last_name' => 'Flenderson']);
        GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $pam->id]);
        GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $toby->id, 'left_on' => '2008-05-15']);

        $cards = new ExportGroupVCard(user: $user, vault: $vault, group: $group)->execute();

        $this->assertSame(2, substr_count($cards, 'BEGIN:VCARD'));
        $this->assertStringContainsString('KIND:group', mb_substr($cards, 0, mb_strpos($cards, 'END:VCARD')));
        $this->assertStringContainsString('FN:Finer things club', $cards);
        $this->assertStringContainsString("MEMBER:urn:uuid:{$pam->uuid}", $cards);
        $this->assertStringContainsString("UID:{$pam->uuid}", $cards);
        $this->assertStringNotContainsString('Flenderson', $cards);

        Queue::assertPushedOn('low', LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::GroupExported);
    }

    #[Test]
    public function it_refuses_a_group_of_another_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = User::factory()->create();
        $vault = Vault::factory()->create(['account_id' => $user->account_id]);
        $group = Group::factory()->create(['vault_id' => Vault::factory()->create(['account_id' => $user->account_id])->id]);

        new ExportGroupVCard(user: $user, vault: $vault, group: $group)->execute();
    }
}
