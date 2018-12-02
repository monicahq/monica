<?php

namespace Tests\Unit\Services\Contact\Avatar;

use Tests\TestCase;
use App\Models\Contact\Contact;
use Illuminate\Http\UploadedFile;
use App\Exceptions\MissingParameterException;
use App\Services\Contact\Avatar\GenerateDefaultAvatar;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GenerateDefaultAvatarTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_generates_a_default_avatar()
    {
        $contact = factory(Contact::class)->create([
            'default_avatar_color' => '#fff',
        ]);

        $request = [
            'contact_id' => $contact->id,
        ];

        $gravatarService = new GenerateDefaultAvatar;
        $contact = $gravatarService->execute($request);

        $this->assertContains(
            'avatars/',
            $contact->avatar_default_url
        );
    }

    public function test_it_fails_if_wrong_parameters_are_given()
    {
        $request = [];

        $this->expectException(MissingParameterException::class);

        $gravatarService = new GenerateDefaultAvatar;
        $url = $gravatarService->execute($request);
    }

    public function test_it_replaces_existing_default_avatar()
    {
        $file = UploadedFile::fake()->image('image.png');

        $contact = factory(Contact::class)->create([
            'default_avatar_color' => '#fff',
            'avatar_default_url' => $file->getPathname(),
        ]);

        $this->assertFileExists($file->getPathname());

        $request = [
            'contact_id' => $contact->id,
        ];

        $gravatarService = new GenerateDefaultAvatar;
        $contact = $gravatarService->execute($request);

        $this->assertContains(
            'avatars/',
            $contact->avatar_default_url
        );
    }
}
