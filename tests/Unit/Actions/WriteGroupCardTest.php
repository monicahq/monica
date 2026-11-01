<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\WriteGroupCard;
use App\Enums\DatePrecisionEnum;
use App\Enums\UserActionEnum;
use App\Http\Dav\VCardReader;
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

class WriteGroupCardTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Vault $vault;

    protected function setUp(): void
    {
        parent::setUp();

        Queue::fake();
        $this->travelTo('2026-10-29 10:00:00');

        $this->user = User::factory()->create();
        $this->vault = Vault::factory()->create(['account_id' => $this->user->account_id]);
    }

    #[Test]
    public function it_creates_a_group_from_a_new_card_with_its_members_joining_today_and_no_contact(): void
    {
        $jim = $this->contact('Jim');
        $pam = $this->contact('Pam');

        $group = new WriteGroupCard(
            user: $this->user,
            vault: $this->vault,
            group: null,
            davUri: 'halperts.vcf',
            card: $this->card('Halpert household', ["MEMBER:urn:uuid:{$jim->uuid}", "MEMBER:urn:uuid:{$pam->uuid}"]),
        )->execute();

        $this->assertInstanceOf(Group::class, $group);
        $this->assertDatabaseHas('groups', [
            'id' => $group->id,
            'vault_id' => $this->vault->id,
            'name' => 'Halpert household',
            'dav_uri' => 'halperts.vcf',
        ]);
        $this->assertSame(2, Contact::query()->where('vault_id', $this->vault->id)->count());

        $memberships = GroupMember::query()->where('group_id', $group->id)->get();
        $this->assertEqualsCanonicalizing([$jim->id, $pam->id], $memberships->pluck('contact_id')->all());
        $this->assertSame('2026-10-29', $memberships->first()->joined_on->format('Y-m-d'));
        $this->assertSame(DatePrecisionEnum::Day, $memberships->first()->joined_precision);

        Queue::assertPushed(LogUserAction::class, fn (LogUserAction $job): bool => $job->action === UserActionEnum::GroupCreation);
    }

    #[Test]
    public function it_reads_the_apple_form_of_the_members(): void
    {
        $dwight = $this->contact('Dwight');

        $group = new WriteGroupCard(
            user: $this->user,
            vault: $this->vault,
            group: null,
            davUri: 'farm.vcf',
            card: new VCardReader("BEGIN:VCARD\r\nVERSION:3.0\r\nUID:farm\r\nFN:Schrute farm\r\nN:Schrute farm;;;;\r\nX-ADDRESSBOOKSERVER-KIND:group\r\nX-ADDRESSBOOKSERVER-MEMBER:urn:uuid:{$dwight->uuid}\r\nEND:VCARD\r\n"),
        )->execute();

        $this->assertSame([$dwight->id], $group->currentMembers()->pluck('contact_id')->all());
    }

    #[Test]
    public function it_finds_a_member_a_device_created_by_the_uid_the_device_gave_it(): void
    {
        $dwight = $this->contact('Dwight', ['vcard' => "BEGIN:VCARD\r\nVERSION:4.0\r\nUID:dwight-from-the-phone\r\nFN:Dwight Schrute\r\nEND:VCARD\r\n"]);

        $group = new WriteGroupCard(
            user: $this->user,
            vault: $this->vault,
            group: null,
            davUri: 'farm.vcf',
            card: $this->card('Schrute farm', ['MEMBER:urn:uuid:dwight-from-the-phone']),
        )->execute();

        $this->assertSame([$dwight->id], $group->currentMembers()->pluck('contact_id')->all());
    }

    #[Test]
    public function it_ignores_a_member_the_vault_does_not_hold(): void
    {
        $jim = $this->contact('Jim');
        $elsewhere = Contact::factory()->create(['account_id' => $this->user->account_id, 'first_name' => 'Karen']);

        $group = new WriteGroupCard(
            user: $this->user,
            vault: $this->vault,
            group: null,
            davUri: 'halperts.vcf',
            card: $this->card('Halpert household', [
                "MEMBER:urn:uuid:{$jim->uuid}",
                "MEMBER:urn:uuid:{$elsewhere->uuid}",
                'MEMBER:urn:uuid:nobody-at-all',
            ]),
        )->execute();

        $this->assertSame([$jim->id], $group->currentMembers()->pluck('contact_id')->all());
        $this->assertSame(0, GroupMember::query()->where('contact_id', $elsewhere->id)->count());
    }

    #[Test]
    public function it_ends_today_the_membership_of_a_member_the_card_leaves_out_and_keeps_its_role_and_start(): void
    {
        $group = Group::factory()->create(['vault_id' => $this->vault->id, 'name' => 'Halpert household']);
        $jim = $this->contact('Jim');
        $pam = $this->contact('Pam');
        GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $jim->id]);
        $leaving = GroupMember::factory()->create([
            'group_id' => $group->id,
            'contact_id' => $pam->id,
            'role' => 'Mother',
            'joined_on' => '2007-05-01',
            'joined_precision' => DatePrecisionEnum::Month,
        ]);

        new WriteGroupCard(
            user: $this->user,
            vault: $this->vault,
            group: $group,
            davUri: "{$group->uuid}.vcf",
            card: $this->card('Halpert household', ["MEMBER:urn:uuid:{$jim->uuid}"]),
        )->execute();

        $leaving->refresh();
        $this->assertSame('2026-10-29', $leaving->left_on?->format('Y-m-d'));
        $this->assertSame(DatePrecisionEnum::Day, $leaving->left_precision);
        $this->assertSame('Mother', $leaving->role);
        $this->assertSame('2007-05-01', $leaving->joined_on?->format('Y-m-d'));
        $this->assertSame(DatePrecisionEnum::Month, $leaving->joined_precision);
        $this->assertSame([$jim->id], $group->currentMembers()->pluck('contact_id')->all());
        $this->assertNull($group->fresh()->dav_uri);
    }

    #[Test]
    public function it_leaves_alone_what_a_card_cannot_carry(): void
    {
        $group = Group::factory()->create([
            'vault_id' => $this->vault->id,
            'name' => 'Scranton branch',
            'description' => 'Dunder Mifflin paper company',
            'started_on' => '2001-01-01',
            'started_precision' => DatePrecisionEnum::Year,
        ]);
        $michael = $this->contact('Michael');
        $devon = $this->contact('Devon', ['is_archived' => true]);
        $toby = $this->contact('Toby');
        GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $michael->id, 'role' => 'Regional manager']);
        $hidden = GroupMember::factory()->create(['group_id' => $group->id, 'contact_id' => $devon->id]);
        $former = GroupMember::factory()->former()->create(['group_id' => $group->id, 'contact_id' => $toby->id, 'role' => 'HR']);

        new WriteGroupCard(
            user: $this->user,
            vault: $this->vault,
            group: $group,
            davUri: 'branch.vcf',
            card: $this->card('Dunder Mifflin Scranton', ["MEMBER:urn:uuid:{$michael->uuid}"]),
        )->execute();

        $group->refresh();
        $this->assertSame('Dunder Mifflin Scranton', $group->name);
        $this->assertSame('Dunder Mifflin paper company', $group->description);
        $this->assertSame('2001-01-01', $group->started_on?->format('Y-m-d'));
        $this->assertSame(DatePrecisionEnum::Year, $group->started_precision);
        $this->assertSame('branch.vcf', $group->dav_uri);
        $this->assertNull($hidden->refresh()->left_on);
        $this->assertSame('2007-01-01', $former->refresh()->left_on?->format('Y-m-d'));
        $this->assertSame('HR', $former->role);
        $this->assertSame(1, GroupMember::query()->where('group_id', $group->id)->where('contact_id', $michael->id)->count());
        $this->assertSame('2026-10-29', $group->updated_at?->format('Y-m-d'));
    }

    #[Test]
    public function it_refuses_a_group_of_another_vault(): void
    {
        $group = Group::factory()->create(['account_id' => $this->user->account_id]);

        $this->expectException(ModelNotFoundException::class);

        new WriteGroupCard(
            user: $this->user,
            vault: $this->vault,
            group: $group,
            davUri: 'branch.vcf',
            card: $this->card('Utica branch', []),
        )->execute();
    }

    /** @param  array<string, mixed>  $attributes */
    private function contact(string $firstName, array $attributes = []): Contact
    {
        return Contact::factory()->create([
            ...$attributes,
            'first_name' => $firstName,
            'vault_id' => $this->vault->id,
            'account_id' => $this->user->account_id,
        ]);
    }

    /** @param  array<int, string>  $members */
    private function card(string $name, array $members): VCardReader
    {
        $lines = implode("\r\n", $members);

        return new VCardReader("BEGIN:VCARD\r\nVERSION:4.0\r\nUID:a-group\r\nKIND:group\r\nFN:{$name}\r\n".($lines === '' ? '' : "{$lines}\r\n")."END:VCARD\r\n");
    }
}
