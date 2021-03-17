<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class AuditHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('audit_histories', function ($table) {
            $table->increments('id');
            $table->integer('author_id')->nullable();
            $table->integer('author_type')->nullable();
            $table->string('target_type')->nullable();
            $table->integer('target_id')->nullable();
            $table->text('detail')->nullable();
            $table->text('old_value')->nullable();
            $table->text('new_value')->nullable();
            $table->string('type')->nullable();
            $table->string('path')->nullable();
            $table->string('column')->nullable();
            $table->string('table')->nullable();
            $table->timestamps();
            $table->engine = 'InnoDB';
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('audit_histories');
    }
}
