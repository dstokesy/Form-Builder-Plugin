<?php namespace Dstokesy\Forms\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class AddsUrlFieldToEntriesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('dstokesy_forms_entries', 'url')) {
            Schema::table('dstokesy_forms_entries', function(Blueprint $table) {
                $table->string('url')->after('user_id')->nullable();
            });
        }
    }

    public function down()
    {
	    Schema::table('dstokesy_forms_entries', function(Blueprint $table) {
		    if (Schema::hasColumn('dstokesy_forms_entries', 'url')) {
			    $table->dropColumn('url');
		    }
	    });
    }
}