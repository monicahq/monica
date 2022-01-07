<?php

namespace App\Console\Commands\Tests;

use Illuminate\Support\Str;
use function Safe\json_decode;
use function Safe\json_encode;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * @codeCoverageIgnore
 */
class UpdateLangs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'setup:updatelang';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update langage files.';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        if (! File::exists(base_path('vendor/laravel-lang/lang'))) {
            throw new \Exception("This command requires laravel-lang/lang package. Run 'composer require --dev laravel-lang/lang' to install it.");
        }

        $en = json_decode(File::get(lang_path('en.json')), true);

        foreach (File::directories(lang_path()) as $lang) {
            $lang = basename($lang);
            $locale = Str::of($lang)->replace('-', '_');
            switch ($lang) {
                case 'zh':
                    $locale = 'zh_CN';
                    break;
                case 'en':
                    continue 2;
            }
            if (File::exists($orig_path = lang_path("$lang.json"))
                && File::exists($trans_path = base_path("vendor/laravel-lang/lang/locales/$locale/$locale.json"))) {
                $lang_orig = json_decode(File::get($orig_path), true);
                $lang_trans = json_decode(File::get($trans_path), true);
                foreach ($en as $key) {
                    $lang_orig[$key] = $lang_trans[$key];
                }
                File::put($orig_path, json_encode($lang_orig, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)."\n");
            }
        }
    }
}
