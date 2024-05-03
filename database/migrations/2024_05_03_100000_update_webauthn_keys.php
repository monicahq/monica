<?php

use Illuminate\Database\Migrations\Migration;
use LaravelWebauthn\Models\WebauthnKey;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        WebauthnKey::select(['id', 'credentialId'])->chunk(200, function ($keys) {
            foreach ($keys as $key) {
                $key->update(['credentialId' => $key->credentialId]);
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
        WebauthnKey::select(['id', 'credentialId'])->chunk(200, function ($keys) {
            foreach ($keys as $key) {
                $key->setRawAttributes(['credentialId' => base64_encode($key->credentialId)]);
                $key->save();
            }
        });
    }
};
