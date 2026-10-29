<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckTranslationConsistency extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:check-consistency {--strict}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check if all translation keys match the English template';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info("Starting validation of JSON translation files...");
        $langPath = lang_path();

        if (!file_exists($langPath)) {
            $this->error("The lang folder was not found.");
            return Command::FAILURE;
        }

        $englishJsonPath = $langPath . DIRECTORY_SEPARATOR . 'en.json';

        if (!file_exists($englishJsonPath)) {
            $this->error("The base template file 'en.json' was not found.");
            return Command::FAILURE;
        }

        $englishJsonData = json_decode(file_get_contents($englishJsonPath), true) ?? [];
        $englishJsonKeys = array_keys($englishJsonData);

        $jsonFiles = glob($langPath . DIRECTORY_SEPARATOR . '*.json');
        $jsonErrorsCount = 0;

        foreach ($jsonFiles as $file) {
            if (basename($file) === 'en.json') {
                continue;
            }

            $currentFileData = json_decode(file_get_contents($file), true) ?? [];
            $keysFileData = array_keys($currentFileData);

            foreach ($englishJsonKeys as $key) {
                if (!array_key_exists($key, $currentFileData)) {
                    $this->error(sprintf('Missing key "%s" in file: %s', $key, basename($file)));
                    $jsonErrorsCount++;
                }
            }

            foreach ($keysFileData as $keyTarget) {

              if (!array_key_exists($keyTarget, $englishJsonData)) {
                if (!$this->option('strict')){
                  $this->warn(sprintf('Extra key "%s" found in file: %s (Not present in en.json)', $keyTarget, basename($file)));

                }  else{
                      $jsonErrorsCount++;
                      $this->error(sprintf('Extra key "%s" found in file: %s (Not present in en.json)', $keyTarget, basename($file)));
                  }
              }
            }
            }


        if ($jsonErrorsCount > 0) {
            $this->warn(sprintf('JSON validation failed: %d missing keys found.', $jsonErrorsCount));
        } else {
            $this->info('All JSON translation files successfully validated.');
        }

        $this->info("\nStarting validation of PHP translation files...");
        $englishPhpFiles = glob($langPath . DIRECTORY_SEPARATOR . 'en' . DIRECTORY_SEPARATOR . '*.php');
        $phpErrorsCount = 0;

        foreach ($englishPhpFiles as $englishFile) {
            $englishFileData = require $englishFile;
            $fileName = basename($englishFile);

            // Locate the same PHP file across all other language directories
            $targetFiles = glob($langPath . DIRECTORY_SEPARATOR . '*' . DIRECTORY_SEPARATOR . $fileName);

            foreach ($targetFiles as $targetFile) {
                if ($targetFile === $englishFile) {
                    continue;
                }

                $targetFileData = require $targetFile;

                $missingKeysData = array_diff_key($englishFileData, $targetFileData);
                $missingKeysDataTarget = array_diff_key($targetFileData, $englishFileData);
                $languageCode = basename(dirname($targetFile));
                
                if (!empty($missingKeysData)) {


                    foreach ($missingKeysData as $missingKey => $value) {
                        $this->error(sprintf('Missing key "%s" in file: %s/%s', $missingKey, $languageCode, $fileName));
                        $phpErrorsCount++;
                    }
                }
                if (!empty($missingKeysDataTarget)){

                  foreach ($missingKeysDataTarget as $extraKey => $value) {
                    $message = sprintf('Extra key "%s" found in file: %s/%s (Not present in en/%s)', $extraKey, $languageCode, $fileName, $fileName);

                    if (!$this->option('strict')){
                        $this->warn($message);
                  } else{
                        $phpErrorsCount++;
                        $this->error($message);
                  }
                  }

                }



            }
        }

        // Final application status return
        if ($jsonErrorsCount > 0 || $phpErrorsCount > 0) {
            $totalErrors = $jsonErrorsCount + $phpErrorsCount;
            $this->error(sprintf("\nValidation failed. Total errors found: %d", $totalErrors));
            return Command::FAILURE;
        }

        $this->info("\nSuccess! All translation keys match the English templates.");
        return Command::SUCCESS;
    }
}
