<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
class CheckTranslationConsistencyTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    private string $pathFakeLangFolder;

    public function setUp(): void
    {
      parent::setUp();
      $this->pathFakeLangFolder = storage_path('framework/testing/lang');

      if (File::exists($this->pathFakeLangFolder)){
        File::deleteDirectory($this->pathFakeLangFolder);
      }
      File::makeDirectory($this->pathFakeLangFolder);

      $this->app->useLangPath($this->pathFakeLangFolder);
    }

    public function tearDown(): void{
      if (File::exists($this->pathFakeLangFolder)){
        File::deleteDirectory($this->pathFakeLangFolder);
      }

      parent::tearDown();
    }

    #[Test]
    public function it_succeeds_when_all_translation_keys_match_perfectly(){
      File::put($this->pathFakeLangFolder . DIRECTORY_SEPARATOR . 'en.json', json_encode(['auth.failed' => 'Invalid credentials']));
      File::put($this->pathFakeLangFolder . DIRECTORY_SEPARATOR . 'ru.json', json_encode(['auth.failed' => 'Неверный логин или пароль']));

      File::makeDirectory($this->pathFakeLangFolder . DIRECTORY_SEPARATOR . 'en', 0755, true);
      File::makeDirectory($this->pathFakeLangFolder . DIRECTORY_SEPARATOR . 'ru', 0755, true);

      File::put($this->pathFakeLangFolder . DIRECTORY_SEPARATOR . 'en/auth.php', "<?php return ['save' => 'Save'];");
      File::put($this->pathFakeLangFolder . DIRECTORY_SEPARATOR . 'ru/auth.php', "<?php return ['save' => 'Сохранить'];");

      $this->artisan('lang:check-consistency')
        ->assertExitCode(0)
        ->expectsOutputToContain('All JSON translation files successfully validated.')
        ->expectsOutputToContain('Success! All translation keys match the English templates.');
    }

    #[Test]
    public function it_fails_when_a_key_is_missing_in_translations(){
      File::put($this->pathFakeLangFolder . DIRECTORY_SEPARATOR . 'en.json', json_encode(['auth.login' => 'Invalid login']));
      File::put($this->pathFakeLangFolder . DIRECTORY_SEPARATOR . 'ru.json', json_encode(['auth.loginer' => 'Неверный логин или пароль']));

      File::makeDirectory($this->pathFakeLangFolder . DIRECTORY_SEPARATOR . 'en', 0755, true);
      File::makeDirectory($this->pathFakeLangFolder . DIRECTORY_SEPARATOR . 'ru', 0755, true);

      File::put($this->pathFakeLangFolder . DIRECTORY_SEPARATOR . 'en/auth.php', "<?php return ['save' => 'Save'];");
      File::put($this->pathFakeLangFolder . DIRECTORY_SEPARATOR . 'ru/auth.php', "<?php return ['savev' => 'Сохранить'];");

      $this->artisan('lang:check-consistency')
        ->assertExitCode(1)
        ->expectsOutputToContain('auth.login')
        ->expectsOutputToContain('save');
    }

    #[Test]
    public function it_warns_about_extra_keys_by_default(): void
    {
        File::put($this->pathFakeLangFolder . DIRECTORY_SEPARATOR . 'en.json', json_encode(['save' => 'Save']));
        File::put($this->pathFakeLangFolder . DIRECTORY_SEPARATOR . 'ru.json', json_encode(['save' => 'Сохранить', 'extra_garbage' => 'Мусор']));

        
        $this->artisan('lang:check-consistency')
            ->assertExitCode(0)
            ->expectsOutputToContain('extra_garbage');
    }

    #[Test]
    public function it_fails_on_extra_keys_in_strict_mode(): void
    {
        File::put($this->pathFakeLangFolder . DIRECTORY_SEPARATOR . 'en.json', json_encode(['save' => 'Save']));
        File::put($this->pathFakeLangFolder . DIRECTORY_SEPARATOR . 'ru.json', json_encode(['save' => 'Сохранить', 'extra_garbage' => 'Мусор']));


        $this->artisan('lang:check-consistency', ['--strict' => true])
            ->assertExitCode(1)
            ->expectsOutputToContain('extra_garbage');
    }
}
