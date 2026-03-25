<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApplicationStatusTypeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('application_status_type', function (Blueprint $table) {
            $table->unsignedBigInteger('application_id');
            $table->unsignedInteger('status_type_id');

            // Foreign keys with proper matching
            $table->foreign('application_id')
                  ->references('id')
                  ->on('applications')
                  ->onDelete('cascade');

            $table->foreign('status_type_id')
                  ->references('id')
                  ->on('status_types')
                  ->onDelete('cascade');

            $table->primary(['application_id', 'status_type_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('application_status_type');
    }
}