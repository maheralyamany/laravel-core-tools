<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        
        if (!Schema::hasTable('block_ips')) {
            Schema::create('block_ips', function (Blueprint $table) {
                $table->string('ip')->primary();
               
                $table->string('state_id')->nullable();
                $table->string('country')->nullable();
                $table->string('description')->nullable();
                
                $table->string('status')->default('block');
                $table->string('note')->nullable();
                $table->timestamps();
            });
        }

       

      
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        
        Schema::dropIfExists('block_ips');
    }
};
