<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

class VdvtAuditHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('vdvt_audit_histories', function ($table) {
            $table->increments('id');
            $table->integer('author_id')->nullable();
            $table->integer('author_type')->nullable();
            $table->text('detail')->nullable();
            $table->string('type')->unsigned()->nullable();
            $table->integer('result')->unsigned()->nullable();
            $table->string('target_type')->nullable();
            $table->integer('target_id')->nullable();
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
        Schema::dropIfExists('vdvt_audit_histories');
    }
}
