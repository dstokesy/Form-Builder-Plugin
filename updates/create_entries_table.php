<?php namespace Dstokesy\Forms\Updates;

use Schema;
use October\Rain\Database\Schema\Blueprint;
use October\Rain\Database\Updates\Migration;

class CreateEntriesTable extends Migration
{
    public function up()
    {
        Schema::create('dstokesy_forms_entries', function(Blueprint $table) {
            $table->engine = 'InnoDB';
            $table->increments('id');
            $table->integer('form_id')->unsigned();
            $table->text('data');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('dstokesy_forms_entries');
    }
}
