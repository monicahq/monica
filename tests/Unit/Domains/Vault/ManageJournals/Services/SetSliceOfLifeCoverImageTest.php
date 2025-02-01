<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Services;

use App\Domains\Vault\ManageJournals\Services\SetSliceOfLifeCoverImage;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\File;
use App\Models\Journal;
use App\Models\SliceOfLife;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SetSliceOfLifeCoverImageTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_adds_a_cover_image(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create(['vault_id' => $vault->id]);
        $slice = SliceOfLife::factory()->create(['journal_id' => $journal->id]);
        $file = File::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $journal, $file, $slice);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new SetSliceOfLifeCoverImage)->execute($request);
    }

    /** @test */
    public function it_fails_if_user_doesnt_belong_to_account(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $account = Account::factory()->create();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create(['vault_id' => $vault->id]);
        $slice = SliceOfLife::factory()->create(['journal_id' => $journal->id]);
        $file = File::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $account, $vault, $journal, $file, $slice);
    }

    /** @test */
    public function it_fails_if_journal_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create();
        $slice = SliceOfLife::factory()->create(['journal_id' => $journal->id]);
        $file = File::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $journal, $file, $slice);
    }

    /** @test */
    public function it_fails_if_slice_doesnt_belong_to_journal(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create(['vault_id' => $vault->id]);
        $slice = SliceOfLife::factory()->create();
        $file = File::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $journal, $file, $slice);
    }

    /** @test */
    public function it_fails_if_file_doesnt_belong_to_vault(): void
    {
        $this->expectException(ModelNotFoundException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create(['vault_id' => $vault->id]);
        $slice = SliceOfLife::factory()->create(['journal_id' => $journal->id]);
        $file = File::factory()->create();

        $this->executeService($regis, $regis->account, $vault, $journal, $file, $slice);
    }

    /** @test */
    public function it_fails_if_user_doesnt_have_right_permission_in_vault(): void
    {
        $this->expectException(NotEnoughPermissionException::class);

        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_VIEW, $vault);
        $journal = Journal::factory()->create(['vault_id' => $vault->id]);
        $slice = SliceOfLife::factory()->create(['journal_id' => $journal->id]);
        $file = File::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $this->executeService($regis, $regis->account, $vault, $journal, $file, $slice);
    }

    private function executeService(User $author, Account $account, Vault $vault, Journal $journal, File $file, SliceOfLife $slice): void
    {
        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'journal_id' => $journal->id,
            'slice_of_life_id' => $slice->id,
            'file_id' => $file->id,
        ];

        (new SetSliceOfLifeCoverImage)->execute($request);

        $this->assertDatabaseHas('slice_of_lives', [
            'id' => $slice->id,
            'file_cover_image_id' => $file->id,
        ]);

        $this->assertDatabaseHas('files', [
            'id' => $file->id,
            'fileable_id' => $slice->id,
            'fileable_type' => SliceOfLife::class,
        ]);
    }
}
