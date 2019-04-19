<?php

use App\Models\Contact\Tag;
use Illuminate\Database\Migrations\Migration;

class FixNonEnglishTabSlugs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Tag::chunk(200, function ($tags) {
            foreach ($tags as $tag) {
                if (empty($tag->name_slug)) {
                    $tag->forceFill([
                        'name_slug' => htmlentities($tag->name),
                    ])->save();
                }
            }
        });
    }
}
