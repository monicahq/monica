<?php

namespace Tests\Unit\Controllers;

use App\Models\Contact;
use App\Models\Group;
use App\Models\Journal;
use App\Models\Post;
use App\Models\SliceOfLife;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class GatesTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_checks_administrator()
    {
        $user1 = $this->createAdministrator();
        $user2 = $this->createUser();

        $this->assertTrue(Gate::forUser($user1)->check('administrator'));
        $this->assertFalse(Gate::forUser($user2)->check('administrator'));
    }

    /** @test */
    public function it_checks_vault_viewer()
    {
        $user = $this->createUser();
        $vault1 = $this->createVaultUser($user, Vault::PERMISSION_VIEW);
        $vault2 = Vault::factory()->create();

        $this->assertTrue(Gate::forUser($user)->check('vault-viewer', $vault1));
        $this->assertFalse(Gate::forUser($user)->check('vault-viewer', $vault2));
    }

    /** @test */
    public function it_checks_vault_editor()
    {
        $user = $this->createUser();
        $vault1 = $this->createVaultUser($user, Vault::PERMISSION_EDIT);
        $vault2 = $this->createVaultUser($user, Vault::PERMISSION_VIEW);

        $this->assertTrue(Gate::forUser($user)->check('vault-editor', $vault1));
        $this->assertFalse(Gate::forUser($user)->check('vault-editor', $vault2));
    }

    /** @test */
    public function it_checks_vault_manager()
    {
        $user = $this->createUser();
        $vault1 = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $vault2 = $this->createVaultUser($user, Vault::PERMISSION_VIEW);

        $this->assertTrue(Gate::forUser($user)->check('vault-manager', $vault1));
        $this->assertFalse(Gate::forUser($user)->check('vault-manager', $vault2));
    }

    /** @test */
    public function it_checks_contact_owner()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $contact1 = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $contact2 = Contact::factory()->create();

        $this->assertTrue(Gate::forUser($user)->check('contact-owner', [$vault, $contact1]));
        $this->assertTrue(Gate::forUser($user)->check('contact-owner', [$vault, $contact1->id]));
        $this->assertFalse(Gate::forUser($user)->check('contact-owner', [$vault, $contact2]));
        $this->assertFalse(Gate::forUser($user)->check('contact-owner', [$vault, $contact2->id]));
    }

    /** @test */
    public function it_checks_group_owner()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $group1 = Group::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $group2 = Group::factory()->create();

        $this->assertTrue(Gate::forUser($user)->check('group-owner', [$vault, $group1]));
        $this->assertTrue(Gate::forUser($user)->check('group-owner', [$vault, $group1->id]));
        $this->assertFalse(Gate::forUser($user)->check('group-owner', [$vault, $group2]));
        $this->assertFalse(Gate::forUser($user)->check('group-owner', [$vault, $group2->id]));
    }

    /** @test */
    public function it_checks_journal_owner()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $journal1 = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $journal2 = Journal::factory()->create();

        $this->assertTrue(Gate::forUser($user)->check('journal-owner', [$vault, $journal1]));
        $this->assertTrue(Gate::forUser($user)->check('journal-owner', [$vault, $journal1->id]));
        $this->assertFalse(Gate::forUser($user)->check('journal-owner', [$vault, $journal2]));
        $this->assertFalse(Gate::forUser($user)->check('journal-owner', [$vault, $journal2->id]));
    }

    /** @test */
    public function it_checks_post_owner()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $post1 = Post::factory()->create([
            'journal_id' => $journal->id,
        ]);
        $post2 = Post::factory()->create();

        $this->assertTrue(Gate::forUser($user)->check('post-owner', [$journal, $post1]));
        $this->assertTrue(Gate::forUser($user)->check('post-owner', [$journal, $post1->id]));
        $this->assertFalse(Gate::forUser($user)->check('post-owner', [$journal, $post2]));
        $this->assertFalse(Gate::forUser($user)->check('post-owner', [$journal, $post2->id]));
    }

    /** @test */
    public function it_checks_slice_of_life_owner()
    {
        $user = $this->createUser();
        $vault = $this->createVaultUser($user, Vault::PERMISSION_MANAGE);
        $journal = Journal::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $sliceOfLife1 = SliceOfLife::factory()->create([
            'journal_id' => $journal->id,
        ]);
        $sliceOfLife2 = SliceOfLife::factory()->create();

        $this->assertTrue(Gate::forUser($user)->check('sliceOfLife-owner', [$journal, $sliceOfLife1]));
        $this->assertTrue(Gate::forUser($user)->check('sliceOfLife-owner', [$journal, $sliceOfLife1->id]));
        $this->assertFalse(Gate::forUser($user)->check('sliceOfLife-owner', [$journal, $sliceOfLife2]));
        $this->assertFalse(Gate::forUser($user)->check('sliceOfLife-owner', [$journal, $sliceOfLife2->id]));
    }
}
