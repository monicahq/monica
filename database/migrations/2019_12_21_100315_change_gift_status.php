<?php

use App\Models\Contact\Gift;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class ChangeGiftStatus extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('gifts', function (Blueprint $table) {
            $table->unsignedInteger('is_for')->change();
            $table->string('status', 8)->after('has_been_received')->default('idea');
            $table->datetime('date')->after('status')->nullable();

            $table->foreign('is_for')->references('id')->on('contacts')->onDelete('set null');
        });

        Gift::chunk(500, function($gifts) {
            foreach ($gifts as $gift) {
                $gift->status = $gift->has_been_offered === 1 ? 'offered' :
                        ($gift->has_been_received === 1 ? 'received' : 'idea');
                $gift->save();
            }
        });

        Schema::table('gifts', function (Blueprint $table) {
            $table->dropColumn('is_an_idea');
            $table->dropColumn('has_been_offered');
            $table->dropColumn('has_been_received');
            $table->dropColumn('offered_at');
            $table->dropColumn('received_at');
        });
    }
}
