<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\UpdateUserInformation;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdateUserInformationTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_updates_the_user_themselves(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'jim.halpert@dundermifflin.com',
        ]);

        $result = new UpdateUserInformation(
            author: $user,
            user: $user,
            email: 'jim.halpert@athlead.com',
            locale: 'fr_FR',
        )->execute();

        $this->assertInstanceOf(User::class, $result);

        $user->refresh();

        $this->assertEquals('jim.halpert@athlead.com', $user->email);
        $this->assertEquals('fr_FR', $user->locale);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserInformationUpdate
                && $job->company->id === $company->id
                && $job->user->id === $user->id,
        );
    }

    #[Test]
    public function it_lets_the_owner_update_another_user(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $company->owner_user_id = $owner->id;
        $company->save();

        $member = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'kevin.malone@dundermifflin.com',
        ]);

        new UpdateUserInformation(
            author: $owner,
            user: $member,
            email: 'kevin.malone@dundermifflin.co.uk',
        )->execute();

        $this->assertEquals('kevin.malone@dundermifflin.co.uk', $member->refresh()->email);
    }

    #[Test]
    public function it_resets_the_email_verification_when_the_email_changes(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'pam.beesly@dundermifflin.com',
            'email_verified_at' => now(),
        ]);

        new UpdateUserInformation(
            author: $user,
            user: $user,
            email: 'pam.halpert@dundermifflin.com',
        )->execute();

        $this->assertNull($user->refresh()->email_verified_at);
    }

    #[Test]
    public function it_keeps_the_email_verification_when_the_email_does_not_change(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'email' => 'pam.beesly@dundermifflin.com',
            'email_verified_at' => now(),
        ]);

        new UpdateUserInformation(
            author: $user,
            user: $user,
            email: 'pam.beesly@dundermifflin.com',
        )->execute();

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    #[Test]
    public function it_throws_when_another_member_updates_a_user(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $company->owner_user_id = $owner->id;
        $company->save();

        $member = User::factory()->create(['company_id' => $company->id]);
        $otherMember = User::factory()->create(['company_id' => $company->id]);

        new UpdateUserInformation(
            author: $otherMember,
            user: $member,
            email: 'toby.flenderson@dundermifflin.com',
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_author_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $user = User::factory()->create();
        $stranger = User::factory()->create();

        new UpdateUserInformation(
            author: $stranger,
            user: $user,
            email: 'creed.bratton@dundermifflin.com',
        )->execute();
    }
}
