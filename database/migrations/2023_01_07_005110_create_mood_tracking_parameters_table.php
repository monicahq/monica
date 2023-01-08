<?php

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
        Schema::create('mood_tracking_parameters', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('vault_id');
            $table->string('label')->nullable();
            $table->string('label_translation_key')->nullable();
            $table->string('hex_color');
            $table->integer('position')->nullable();
            $table->timestamps();
            $table->foreign('vault_id')->references('id')->on('vaults')->onDelete('cascade');
        });

        Vault::chunk(200, function ($vaults) {
            foreach ($vaults as $vault) {
                DB::table('mood_tracking_parameters')->insert([
                    'vault_id' => $vault->id,
                    'label_translation_key' => trans('vault.settings_mood_tracking_parameters_awesome'),
                    'hex_color' => 'bg-lime-500',
                    'position' => 1,
                ]);

                DB::table('mood_tracking_parameters')->insert([
                    'vault_id' => $vault->id,
                    'label_translation_key' => trans('vault.settings_mood_tracking_parameters_good'),
                    'hex_color' => 'bg-lime-300',
                    'position' => 2,
                ]);

                DB::table('mood_tracking_parameters')->insert([
                    'vault_id' => $vault->id,
                    'label_translation_key' => trans('vault.settings_mood_tracking_parameters_meh'),
                    'hex_color' => 'bg-cyan-600',
                    'position' => 3,
                ]);

                DB::table('mood_tracking_parameters')->insert([
                    'vault_id' => $vault->id,
                    'label_translation_key' => trans('vault.settings_mood_tracking_parameters_bad'),
                    'hex_color' => 'bg-orange-300',
                    'position' => 4,
                ]);

                DB::table('mood_tracking_parameters')->insert([
                    'vault_id' => $vault->id,
                    'label_translation_key' => trans('vault.settings_mood_tracking_parameters_awful'),
                    'hex_color' => 'bg-red-700',
                    'position' => 5,
                ]);
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('mood_tracking_parameters');
    }
};
