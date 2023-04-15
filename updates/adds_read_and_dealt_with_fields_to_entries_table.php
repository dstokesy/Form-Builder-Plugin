<?php namespace Dstokesy\Forms\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class AddsReadAndDealtWithFieldsToEntriesTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('dstokesy_forms_entries', 'is_read')) {
            Schema::table('dstokesy_forms_entries', function(Blueprint $table) {
                $table->boolean('is_read')->after('data')->nullable();
            });
        }

        if (!Schema::hasColumn('dstokesy_forms_entries', 'dealt_with')) {
            Schema::table('dstokesy_forms_entries', function(Blueprint $table) {
                $table->boolean('dealt_with')->after('is_read')->nullable();
            });
        }

        if (!Schema::hasColumn('dstokesy_forms_entries', 'dealt_with_backend_user_id')) {
            Schema::table('dstokesy_forms_entries', function(Blueprint $table) {
                $table->boolean('dealt_with_backend_user_id')->after('dealt_with')->nullable();
            });
        }

        if (!Schema::hasColumn('dstokesy_forms_entries', 'dealt_with_at')) {
            Schema::table('dstokesy_forms_entries', function(Blueprint $table) {
                $table->timestamp('dealt_with_at')->after('dealt_with_backend_user_id')->nullable();
            });
        }
    }

    public function down()
    {
	    Schema::table('dstokesy_forms_entries', function(Blueprint $table) {
		    if (Schema::hasColumn('dstokesy_forms_entries', 'is_read')) {
			    $table->dropColumn('is_read');
		    }

		    if (Schema::hasColumn('dstokesy_forms_entries', 'dealt_with')) {
			    $table->dropColumn('dealt_with');
		    }

		    if (Schema::hasColumn('dstokesy_forms_entries', 'dealt_with_backend_user_id')) {
			    $table->dropColumn('dealt_with_backend_user_id');
		    }

		    if (Schema::hasColumn('dstokesy_forms_entries', 'dealt_with_at')) {
			    $table->dropColumn('dealt_with_at');
		    }
	    });
    }
}