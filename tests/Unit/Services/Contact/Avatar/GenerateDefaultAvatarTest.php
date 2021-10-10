<?php

namespace Tests\Unit\Services\Contact\Avatar;

use Tests\TestCase;
use App\Models\Contact\Contact;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use App\Services\Contact\Avatar\GenerateDefaultAvatar;
use Illuminate\Foundation\Testing\DatabaseTransactions;

class GenerateDefaultAvatarTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function it_generates_a_default_avatar()
    {
        $contact = factory(Contact::class)->create([
            'default_avatar_color' => '#000',
        ]);

        $request = [
            'contact_id' => $contact->id,
        ];

        $contact = app(GenerateDefaultAvatar::class)->execute($request);

        $this->assertStringContainsString(
            'avatars/',
            $contact->avatar_default_url
        );
    }

    /** @test */
    public function it_fails_if_wrong_parameters_are_given()
    {
        $request = [];

        $this->expectException(ValidationException::class);
        app(GenerateDefaultAvatar::class)->execute($request);
    }

    /** @test */
    public function it_replaces_existing_default_avatar()
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

        $contact = app(GenerateDefaultAvatar::class)->execute($request);

        $this->assertStringContainsString(
            'avatars/',
            $contact->avatar_default_url
        );
    }
}
