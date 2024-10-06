<?php

namespace Tests\Unit\Domains\Vault\ManageJournals\Services;

use App\Domains\Vault\ManageJournals\Services\RemoveSliceOfLifeCoverImage;
use App\Exceptions\NotEnoughPermissionException;
use App\Models\Account;
use App\Models\File;
use App\Models\Journal;
use App\Models\SliceOfLife;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RemoveSliceOfLifeCoverImageTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_removes_a_cover_image(): void
    {
        $regis = $this->createUser();
        $vault = $this->createVault($regis->account);
        $vault = $this->setPermissionInVault($regis, Vault::PERMISSION_EDIT, $vault);
        $journal = Journal::factory()->create(['vault_id' => $vault->id]);
        $slice = SliceOfLife::factory()->create(['journal_id' => $journal->id]);

        $this->executeService($regis, $regis->account, $vault, $journal, $slice);
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given(): void
    {
        $request = [
            'title' => 'Ross',
        ];

        $this->expectException(ValidationException::class);
        (new RemoveSliceOfLifeCoverImage)->execute($request);
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

        $this->executeService($regis, $account, $vault, $journal, $slice);
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

        $this->executeService($regis, $regis->account, $vault, $journal, $slice);
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

        $this->executeService($regis, $regis->account, $vault, $journal, $slice);
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

        $this->executeService($regis, $regis->account, $vault, $journal, $slice);
    }

    private function executeService(User $author, Account $account, Vault $vault, Journal $journal, SliceOfLife $slice): void
    {
        Event::fake();

        $file = File::factory()->create([
            'vault_id' => $vault->id,
        ]);

        $slice->file_cover_image_id = $file->id;
        $slice->save();

        $request = [
            'account_id' => $account->id,
            'author_id' => $author->id,
            'vault_id' => $vault->id,
            'journal_id' => $journal->id,
            'slice_of_life_id' => $slice->id,
        ];

        (new RemoveSliceOfLifeCoverImage)->execute($request);

        $this->assertDatabaseHas('slice_of_lives', [
            'id' => $slice->id,
            'file_cover_image_id' => null,
        ]);

        $this->assertDatabaseMissing('files', [
            'id' => $file->id,
        ]);
    }
}
