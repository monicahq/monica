<?php

namespace Tests\Unit\Models;

use App\Models\ContactTask;
use App\Models\Note;
use App\Models\User;
use App\Models\UserNotificationChannel;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class UserTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_has_one_account()
    {
        $regis = User::factory()->create();

        $this->assertTrue($regis->account()->exists());
    }

    /** @test */
    public function it_has_many_vaults(): void
    {
        $regis = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $regis->account_id,
        ]);

        $regis->vaults()->sync([$vault->id => ['permission' => Vault::PERMISSION_MANAGE]]);

        $this->assertTrue($regis->vaults()->exists());
    }

    /** @test */
    public function it_has_many_notes(): void
    {
        $regis = User::factory()->create();
        Note::factory()->count(2)->create([
            'author_id' => $regis->id,
        ]);

        $this->assertTrue($regis->notes()->exists());
    }

    /** @test */
    public function it_has_many_notification_channels(): void
    {
        $regis = User::factory()->create();
        UserNotificationChannel::factory()->count(2)->create([
            'user_id' => $regis->id,
        ]);

        $this->assertTrue($regis->notificationChannels()->exists());
    }

    /** @test */
    public function it_has_many_contact_tasks(): void
    {
        $regis = User::factory()->create();
        ContactTask::factory()->count(2)->create([
            'author_id' => $regis->id,
        ]);

        $this->assertTrue($regis->contactTasks()->exists());
    }

    /** @test */
    public function it_returns_the_name_attribute(): void
    {
        $rachel = User::factory()->create([
            'first_name' => 'Dwight',
            'last_name' => 'Schrute',
        ]);

        $this->assertEquals(
            'Dwight Schrute',
            $rachel->name,
        );
    }
}
