<?php

namespace Tests\Unit\Domains\Vault\ManageFiles\Web\ViewHelpers;

use App\Models\Avatar;
use App\Models\Contact;
use App\Models\File;
use App\Models\User;
use App\Models\Vault;
use App\Vault\ManageFiles\Web\ViewHelpers\VaultFileIndexViewHelper;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;
use function env;

class VaultFileIndexViewHelperTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_gets_the_data_needed_for_the_view(): void
    {
        Carbon::setTestNow(Carbon::create(2022, 1, 1));
        $user = User::factory()->create();
        $vault = Vault::factory()->create([
            'account_id' => $user->account_id,
        ]);
        $contact = Contact::factory()->create([
            'vault_id' => $vault->id,
        ]);
        $avatar = Avatar::factory()->create([
            'contact_id' => $contact->id,
        ]);
        $contact->avatar_id = $avatar->id;
        $contact->save();
        $file = File::factory()->create([
            'contact_id' => $contact->id,
            'type' => File::TYPE_DOCUMENT,
            'created_at' => '2022-01-01',
            'size' => '12345',
        ]);

        $files = File::all();

        $array = VaultFileIndexViewHelper::data($files, $user, $vault);
        $this->assertCount(2, $array);
        $this->assertArrayHasKey('files', $array);
        $this->assertArrayHasKey('statistics', $array);

        $this->assertEquals(
            [
                0 => [
                    'id' => $file->id,
                    'download_url' => $file->cdn_url,
                    'name' => $file->name,
                    'mime_type' => $file->mime_type,
                    'size' => '12.06kB',
                    'created_at' => 'Jan 01, 2022',
                    'url' => [
                        'destroy' => env('APP_URL') . '/vaults/' . $contact->vault->id . '/contacts/' . $contact->id . '/documents/' . $file->id,
                    ],
                    'contact' => [
                        'id' => $contact->id,
                        'name' => $contact->name,
                        'avatar' => '123',
                        'url' => [
                            'show' => env('APP_URL') . '/vaults/' . $vault->id . '/contacts/' . $contact->id,
                        ],
                    ],
                ],
            ],
            $array['files']->toArray()
        );
    }
}
