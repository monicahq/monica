<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateVersion extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'monica:version';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Saves the last commit SHA and date';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle(): void
    {
        $commitHash = trim(exec('git log --pretty="%h" -n1 main'));
        $commitDate = trim(exec('git log -n1 --pretty=%ct main'));

        $disk = Storage::build([
            'driver' => 'local',
            'root' => base_path(),
        ]);

        $data = [
            'date' => $commitDate,
            'sha' => $commitHash,
        ];

        $disk->put('version.json', json_encode($data));
    }
}
