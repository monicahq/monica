<?php

/**
 * vCard Import Feature - Import Controller Tests
 *
 * @author Clément ABRAHAM <https://github.com/cabraham2>
 * @version 1.0.0
 * @created 2026-01-08
 * @updated 2026-01-08
 * @license MIT
 *
 * Feature tests for ContactImportController.
 * Tests complete import workflow from upload to final import.
 */

namespace Tests\Feature\Controllers;

use App\Models\Contact;
use App\Models\User;
use App\Models\Vault;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContactImportControllerTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_shows_the_import_page()
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $vault->users()->attach($user, [
            'permission' => Vault::PERMISSION_EDIT,
        ]);

        $response = $this->actingAs($user)
            ->get("/vaults/{$vault->id}/contacts/import");

        $response->assertStatus(200);
        $response->assertInertia(function ($page) {
            $page->component('Vault/Contact/Import/Index');
        });
    }

    /** @test */
    public function it_uploads_and_parses_a_vcard_file()
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $vault->users()->attach($user, [
            'permission' => Vault::PERMISSION_EDIT,
        ]);

        $vcardContent = "BEGIN:VCARD\n".
            "VERSION:3.0\n".
            "N:Doe;John;;;\n".
            "FN:John Doe\n".
            "EMAIL:john@example.com\n".
            "END:VCARD";

        $file = UploadedFile::fake()->createWithContent('contacts.vcf', $vcardContent);

        $response = $this->actingAs($user)
            ->post("/vaults/{$vault->id}/contacts/import/upload", [
                'file' => $file,
            ]);

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'contacts',
                'session_key',
                'total',
                'errors',
            ],
        ]);

        $data = $response->json('data');
        $this->assertEquals(1, $data['total']);
        $this->assertCount(1, $data['contacts']);
        $this->assertEquals('John Doe', $data['contacts'][0]['name']);
    }

    /** @test */
    public function it_imports_selected_contacts()
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $vault->users()->attach($user, [
            'permission' => Vault::PERMISSION_EDIT,
        ]);

        $vcardContent = "BEGIN:VCARD\n".
            "VERSION:3.0\n".
            "N:Doe;John;;;\n".
            "FN:John Doe\n".
            "EMAIL:john@example.com\n".
            "END:VCARD\n".
            "BEGIN:VCARD\n".
            "VERSION:3.0\n".
            "N:Smith;Jane;;;\n".
            "FN:Jane Smith\n".
            "EMAIL:jane@example.com\n".
            "END:VCARD";

        // First upload
        $file = UploadedFile::fake()->createWithContent('contacts.vcf', $vcardContent);
        $uploadResponse = $this->actingAs($user)
            ->post("/vaults/{$vault->id}/contacts/import/upload", [
                'file' => $file,
            ]);

        $sessionKey = $uploadResponse->json('data.session_key');

        // Then import
        $response = $this->actingAs($user)
            ->post("/vaults/{$vault->id}/contacts/import", [
                'session_key' => $sessionKey,
                'selected_indices' => [0, 1], // Import both contacts
            ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertEquals(2, $data['success_count']);
        $this->assertEquals(0, $data['error_count']);

        // Verify contacts were created
        $this->assertEquals(2, $vault->contacts()->count());
    }

    /** @test */
    public function it_imports_only_selected_contacts()
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $vault->users()->attach($user, [
            'permission' => Vault::PERMISSION_EDIT,
        ]);

        $vcardContent = "BEGIN:VCARD\n".
            "VERSION:3.0\n".
            "N:Doe;John;;;\n".
            "FN:John Doe\n".
            "END:VCARD\n".
            "BEGIN:VCARD\n".
            "VERSION:3.0\n".
            "N:Smith;Jane;;;\n".
            "FN:Jane Smith\n".
            "END:VCARD";

        // Upload
        $file = UploadedFile::fake()->createWithContent('contacts.vcf', $vcardContent);
        $uploadResponse = $this->actingAs($user)
            ->post("/vaults/{$vault->id}/contacts/import/upload", [
                'file' => $file,
            ]);

        $sessionKey = $uploadResponse->json('data.session_key');

        // Import only the first contact
        $response = $this->actingAs($user)
            ->post("/vaults/{$vault->id}/contacts/import", [
                'session_key' => $sessionKey,
                'selected_indices' => [0], // Only first contact
            ]);

        $response->assertStatus(200);
        $data = $response->json('data');
        
        $this->assertEquals(1, $data['success_count']);
        $this->assertEquals(1, $vault->contacts()->count());
    }

    /** @test */
    public function it_rejects_invalid_file_types()
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $vault->users()->attach($user, [
            'permission' => Vault::PERMISSION_EDIT,
        ]);

        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAs($user)
            ->post("/vaults/{$vault->id}/contacts/import/upload", [
                'file' => $file,
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function it_cancels_import_and_cleans_up_session()
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $vault->users()->attach($user, [
            'permission' => Vault::PERMISSION_EDIT,
        ]);

        // Set up a session
        session(['vcard_import_session_key' => 'test_key']);
        session(['test_key' => 'some_content']);

        $response = $this->actingAs($user)
            ->delete("/vaults/{$vault->id}/contacts/import");

        $response->assertStatus(200);
        $this->assertNull(session('vcard_import_session_key'));
        $this->assertNull(session('test_key'));
    }

    /** @test */
    public function it_requires_editor_permission()
    {
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $vault->users()->attach($user, [
            'permission' => Vault::PERMISSION_VIEW, // View only
        ]);

        $response = $this->actingAs($user)
            ->get("/vaults/{$vault->id}/contacts/import");

        $response->assertStatus(403);
    }
}
