<?php namespace Dstokesy\Forms\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateFormsTable extends Migration
{
    public function up()
    {
        Schema::create('dstokesy_forms', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->string('name')->nullable();
            $table->string('button_text')->nullable();
            $table->text('fields')->nullable();
            $table->text('event_tracking')->nullable();
            $table->string('error_message')->nullable();
            $table->string('success_message')->nullable();
            $table->string('success_page')->nullable();
            $table->boolean('add_to_mailing_list')->nullable();
            $table->boolean('user_success_email_checkbox')->nullable();
            $table->string('user_email_subject')->nullable();
            $table->text('user_email_content')->nullable();
            $table->string('user_email_bcc')->nullable();
            $table->boolean('admin_success_email_checkbox')->nullable();
            $table->string('admin_email_recipient')->nullable();
            $table->string('admin_email_cc')->nullable();
            $table->string('admin_email_bcc')->nullable();
            $table->string('admin_email_subject')->nullable();
            $table->boolean('is_live')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('dstokesy_forms');
    }
}
