<?php

declare(strict_types=1);

namespace Tests\Unit\Actions;

use App\Actions\DestroyUser;
use App\Enums\UserActionEnum;
use App\Jobs\LogUserAction;
use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DestroyUserTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_destroys_a_user_of_the_company(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $company->owner_user_id = $owner->id;
        $company->save();

        $member = User::factory()->create(['company_id' => $company->id]);

        new DestroyUser(
            author: $owner,
            user: $member,
        )->execute();

        $this->assertSoftDeleted($member);

        Queue::assertPushedOn(
            queue: 'low',
            job: LogUserAction::class,
            callback: fn (LogUserAction $job): bool => $job->action === UserActionEnum::UserDeletion
                && $job->company->id === $company->id
                && $job->user->id === $owner->id
                && $job->parameters === ['email' => $member->email],
        );
    }

    #[Test]
    public function it_throws_when_the_author_is_not_the_owner(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $company->owner_user_id = $owner->id;
        $company->save();

        $member = User::factory()->create(['company_id' => $company->id]);
        $otherMember = User::factory()->create(['company_id' => $company->id]);

        new DestroyUser(
            author: $otherMember,
            user: $member,
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_owner_deletes_themselves(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $company->owner_user_id = $owner->id;
        $company->save();

        new DestroyUser(
            author: $owner,
            user: $owner,
        )->execute();
    }

    #[Test]
    public function it_throws_when_the_user_belongs_to_another_company(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $company = Company::factory()->create();
        $owner = User::factory()->create(['company_id' => $company->id]);
        $company->owner_user_id = $owner->id;
        $company->save();

        $stranger = User::factory()->create();

        new DestroyUser(
            author: $owner,
            user: $stranger,
        )->execute();
    }
}
