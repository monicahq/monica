<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Account;
use App\Models\File;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class FileTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function it_belongs_to_an_account(): void
    {
        $file = File::factory()->create();

        $this->assertTrue($file->account()->exists());
        $this->assertInstanceOf(Account::class, $file->account);
    }

    #[Test]
    public function it_belongs_to_the_vault_it_was_put_in(): void
    {
        $vault = Vault::factory()->create();
        $file = File::factory()->create(['account_id' => $vault->account_id, 'vault_id' => $vault->id]);

        $this->assertTrue($file->vault()->exists());
        $this->assertTrue($file->vault->is($vault));
    }

    /**
     * A user photo belongs to the account rather than to one of its
     * compartments.
     */
    #[Test]
    public function it_belongs_to_no_vault_when_it_is_the_accounts_own(): void
    {
        $file = File::factory()->create();

        $this->assertNull($file->vault_id);
        $this->assertNull($file->vault);
    }

    #[Test]
    public function it_knows_who_uploaded_it(): void
    {
        $user = User::factory()->create();
        $file = File::factory()->create(['account_id' => $user->account_id, 'uploaded_by_user_id' => $user->id]);

        $this->assertTrue($file->uploadedBy->is($user));
    }

    #[Test]
    public function it_is_written_under_the_account_and_then_the_vault(): void
    {
        $this->assertEquals('accounts/12', File::directoryFor(12, null));
        $this->assertEquals('accounts/12/vaults/7', File::directoryFor(12, 7));
    }

    #[Test]
    public function it_is_an_orphan_when_nothing_points_at_it(): void
    {
        $orphan = File::factory()->create();
        $used = File::factory()->used()->create();

        $this->assertTrue($orphan->isOrphan());
        $this->assertFalse($used->isOrphan());
    }

    #[Test]
    public function it_is_looked_up_by_its_uuid(): void
    {
        $file = File::factory()->create();

        $this->assertEquals('uuid', $file->getRouteKeyName());
        $this->assertNotNull($file->uuid);
    }

    #[Test]
    public function it_reads_the_sizes_of_a_picture(): void
    {
        $file = File::factory()->image()->create();

        $this->assertEquals(1200, $file->width);
        $this->assertEquals(800, $file->height);
    }
}
