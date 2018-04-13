<?php

use App\Relationship;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class FixRelationshipsDuplicate extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $relationships = Relationship::select('relationship_type_id', 'contact_is', 'of_contact')->get();
        \Log::info($relationships->count());

        $unique = $relationships->unique();
        \Log::info($unique->count());        
    }
}
