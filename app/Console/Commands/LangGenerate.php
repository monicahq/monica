<?php
/**
  *  This file is part of Monica.
  *
  *  Monica is free software: you can redistribute it and/or modify
  *  it under the terms of the GNU Affero General Public License as published by
  *  the Free Software Foundation, either version 3 of the License, or
  *  (at your option) any later version.
  *
  *  Monica is distributed in the hope that it will be useful,
  *  but WITHOUT ANY WARRANTY; without even the implied warranty of
  *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  *  GNU Affero General Public License for more details.
  *
  *  You should have received a copy of the GNU Affero General Public License
  *  along with Monica.  If not, see <https://www.gnu.org/licenses/>.
  **/


namespace App\Console\Commands;

use DirectoryIterator;
use Illuminate\Console\Command;

class LangGenerate extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate i18n json assets';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $dirs = new DirectoryIterator(resource_path('lang').'/');

        foreach ($dirs as $dir) {
            if (! $dir->isDir()) {
                continue;
            }

            $lang = $dir->getFilename();
            if ($lang == '.' || $lang == '..') {
                continue;
            }

            $this->call('lang:js', [
                '--json' => true,
                '--source' => $dir->getPathname(),
                'target' => 'public/js/langs/'.$lang.'.json',
            ]);
        }
    }
}
