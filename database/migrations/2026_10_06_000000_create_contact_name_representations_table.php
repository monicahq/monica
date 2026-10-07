<?php

declare(strict_types=1);

use App\Models\Contact;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_name_representations', function (Blueprint $table): void {
            $table->id()->comment('primary key');
            $table->uuid()->unique()->comment('identifier used by the API, and the PROP-ID written on the vCard');
            $table->foreignIdFor(Contact::class)->comment('contact whose current name this is one way of writing')->constrained()->cascadeOnDelete();
            $table->string('kind', 32)->comment('written, transliteration or phonetic');
            $table->string('language_tag', 35)->nullable()->comment('BCP 47 language tag, such as ko or ja');
            $table->char('script_code', 4)->nullable()->comment('ISO 15924 script code, such as Hang or Latn');
            $table->string('phonetic_system', 32)->nullable()->comment('vCard PHONETIC token, such as ipa or piny, set only on a phonetic representation');
            $table->string('formatted_name')->nullable()->comment('the name exactly as it should be shown, the vCard FN');
            $table->string('prefix', 50)->nullable()->comment('civility, such as Dr or Mrs');
            $table->string('given_name')->nullable()->comment('given name');
            $table->string('additional_name')->nullable()->comment('other given names');
            $table->string('family_name')->nullable()->comment('family name');
            $table->string('suffix', 50)->nullable()->comment('suffix, such as Jr or PhD');
            $table->string('name_order', 32)->nullable()->comment('order this name reads in, null to follow the user preference');
            $table->string('sort_name', 511)->comment('family, given and additional name, else the formatted name, lowercased');
            $table->string('search_key', 511)->index()->comment('the text this representation shows, folded for the language of the account');
            $table->string('match_key', 64)->index()->comment('folded family name, else given name, else formatted name, used to group duplicates');
            $table->string('vcard_alt_id', 64)->nullable()->comment('ALTID the representation arrived with from a device');
            $table->string('vcard_property_id')->nullable()->comment('PROP-ID a device gave the representation, when it is not our uuid');
            $table->unsignedInteger('position')->comment('order on screen, starting at 1');
            $table->timestamps();
        });
    }
};
