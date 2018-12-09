<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddEmotionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('emotions_primary', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->timestamps();
        });

        Schema::create('emotions_secondary', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('emotion_primary_id');
            $table->string('name');
            $table->timestamps();
            $table->foreign('emotion_primary_id')->references('id')->on('emotions_primary')->onDelete('cascade');
        });

        Schema::create('emotions', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('emotion_primary_id');
            $table->unsignedInteger('emotion_secondary_id');
            $table->string('name');
            $table->timestamps();
            $table->foreign('emotion_primary_id')->references('id')->on('emotions_primary')->onDelete('cascade');
            $table->foreign('emotion_secondary_id')->references('id')->on('emotions_secondary')->onDelete('cascade');
        });

        $emotionPrimaryId = DB::table('emotions_primary')->insertGetId(['name' => 'love']);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'affection']);

        DB::table('emotions')->insert(['name' => 'adoration', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'affection', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'love', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'fondness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'liking', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'attraction', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'caring', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'tenderness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'compassion', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'sentimentality', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'lust']);

        DB::table('emotions')->insert(['name' => 'arousal', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'desire', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'lust', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'passion', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'infatuation', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'longing']);

        DB::table('emotions')->insert(['name' => 'longing', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionPrimaryId = DB::table('emotions_primary')->insertGetId(['name' => 'joy']);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'cheerfulness']);

        DB::table('emotions')->insert(['name' => 'amusement', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'bliss', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'cheerfulness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'gaiety', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'glee', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'jolliness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'joviality', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'joy', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'delight', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'enjoyment', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'gladness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'happiness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'jubilation', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'elation', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'satisfaction', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'ecstasy', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'euphoria', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'zest']);

        DB::table('emotions')->insert(['name' => 'enthusiasm', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'zeal', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'zest', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'excitement', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'thrill', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'exhilaration', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'contentment']);

        DB::table('emotions')->insert(['name' => 'contentment', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'pleasure', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'contentment']);

        DB::table('emotions')->insert(['name' => 'pride', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'pleasure', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'optimism']);

        DB::table('emotions')->insert(['name' => 'eagerness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'hope', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'hope', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'enthrallment']);

        DB::table('emotions')->insert(['name' => 'enthrallment', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'rapture', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'relief']);

        DB::table('emotions')->insert(['name' => 'relief', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionPrimaryId = DB::table('emotions_primary')->insertGetId(['name' => 'surprise']);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'surprise']);

        DB::table('emotions')->insert(['name' => 'amazement', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'surprise', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'astonishment', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionPrimaryId = DB::table('emotions_primary')->insertGetId(['name' => 'anger']);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'irritation']);

        DB::table('emotions')->insert(['name' => 'aggravation', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'irritation', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'agitation', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'annoyance', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'grouchiness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'grumpiness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'exasperation']);

        DB::table('emotions')->insert(['name' => 'exasperation', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'frustration', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'rage']);

        DB::table('emotions')->insert(['name' => 'anger', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'rage', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'outrage', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'fury', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'wrath', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'hostility', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'ferocity', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'bitterness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'hate', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'loathing', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'scorn', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'spite', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'vengefulness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'dislike', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'resentment', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'disgust']);

        DB::table('emotions')->insert(['name' => 'disgust', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'revulsion', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'contempt', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'envy']);

        DB::table('emotions')->insert(['name' => 'envy', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'jealousy', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionPrimaryId = DB::table('emotions_primary')->insertGetId(['name' => 'sadness']);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'suffering']);

        DB::table('emotions')->insert(['name' => 'agony', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'suffering', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'hurt', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'anguish', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'sadness']);

        DB::table('emotions')->insert(['name' => 'depression', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'despair', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'hopelessness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'gloom', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'glumness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'sadness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'unhappiness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'grief', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'sorrow', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'woe', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'misery', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'melancholy', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'disappointment']);

        DB::table('emotions')->insert(['name' => 'dismay', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'disappointment', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'displeasure', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'shame']);

        DB::table('emotions')->insert(['name' => 'guilt', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'shame', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'regret', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'remorse', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'neglect']);

        DB::table('emotions')->insert(['name' => 'alienation', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'isolation', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'neglect', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'loneliness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'rejection', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'homesickness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'defeat', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'dejection', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'insecurity', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'embarrassment', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'humiliation', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'insult', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'sympathy']);

        DB::table('emotions')->insert(['name' => 'pity', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'sympathy', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionPrimaryId = DB::table('emotions_primary')->insertGetId(['name' => 'fear']);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'horror']);

        DB::table('emotions')->insert(['name' => 'alarm', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'shock', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'fear', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'fright', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'horror', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'terror', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'panic', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'hysteria', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'mortification', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);

        $emotionSecondaryId = DB::table('emotions_secondary')->insertGetId(['emotion_primary_id' => $emotionPrimaryId, 'name' => 'nervousness']);

        DB::table('emotions')->insert(['name' => 'anxiety', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'nervousness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'tenseness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'uneasiness', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'apprehension', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'worry', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'distress', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
        DB::table('emotions')->insert(['name' => 'dread', 'emotion_primary_id' => $emotionPrimaryId, 'emotion_secondary_id' => $emotionSecondaryId]);
    }
}
