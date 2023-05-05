<?php

use App\Models\Account;
use App\Models\Contact;
use App\Models\Journal;
use App\Models\Post;
use App\Models\PostTemplate;
use App\Models\Tag;
use App\Models\Vault;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('post_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Account::class)->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('label_translation_key')->nullable();
            $table->integer('position');
            $table->boolean('can_be_deleted')->default(true);
            $table->timestamps();
        });

        Schema::create('post_template_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(PostTemplate::class)->constrained()->cascadeOnDelete();
            $table->string('label')->nullable();
            $table->string('label_translation_key')->nullable();
            $table->integer('position');
            $table->boolean('can_be_deleted')->default(true);
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Journal::class)->constrained()->cascadeOnDelete();
            $table->boolean('published')->default(false);
            $table->string('title')->nullable();
            $table->integer('view_count')->default(0);
            $table->datetime('written_at');
            $table->timestamps();
        });

        Schema::create('post_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Post::class)->constrained()->cascadeOnDelete();
            $table->integer('position');
            $table->string('label');
            $table->text('content')->nullable();
            $table->timestamps();
        });

        Schema::create('contact_post', function (Blueprint $table) {
            $table->foreignIdFor(Post::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Contact::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(Vault::class)->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('slug');
            $table->timestamps();
        });

        Schema::create('post_tag', function (Blueprint $table) {
            $table->foreignIdFor(Tag::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(Post::class)->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('post_tag');
        Schema::dropIfExists('tags');
        Schema::dropIfExists('contact_post');
        Schema::dropIfExists('post_sections');
        Schema::dropIfExists('posts');
        Schema::dropIfExists('post_template_sections');
        Schema::dropIfExists('post_templates');
    }
};
