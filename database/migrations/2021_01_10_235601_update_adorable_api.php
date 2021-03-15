<?php

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

class UpdateAdorableAPI extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $adorable_api = Str::finish(config('monica.adorable_api'), '/');

        DB::table('contacts')
            ->where('avatar_adorable_url', 'like', 'https://api.adorable.io%')
            ->orWhere('avatar_adorable_url', 'like', "$adorable_api%")
            ->select('id', 'avatar_adorable_url')
            ->chunkById(1000, function ($contacts) use ($adorable_api) {
                foreach ($contacts as $contact) {
                    $adorable_url = Str::of($contact->avatar_adorable_url)
                        ->replace('https://api.adorable.io/avatars/', '')
                        ->replace($adorable_api, '');
                    DB::table('contacts')
                        ->where('id', $contact->id)
                        ->update([
                            'avatar_adorable_url' => (string) $adorable_url,
                        ]);
                }
            });
    }
}
