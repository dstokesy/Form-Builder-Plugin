<?php namespace Dstokesy\Forms\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class AddsAdminEmailLinkOnlyToFormsTable extends Migration
{
    public function up()
    {
        if (!Schema::hasColumn('dstokesy_forms', 'admin_email_link_only')) {
            Schema::table('dstokesy_forms', function(Blueprint $table) {
                $table->boolean('admin_email_link_only')->after('admin_success_email_checkbox')->nullable();
            });
        }
    }

    public function down()
    {
	    Schema::table('dstokesy_forms', function(Blueprint $table) {
		    if (Schema::hasColumn('dstokesy_forms', 'admin_email_link_only')) {
			    $table->dropColumn('admin_email_link_only');
		    }
	    });
    }
}