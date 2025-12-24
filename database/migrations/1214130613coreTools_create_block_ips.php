<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Maher\CoreTools\Security\Helpers\SecurityHelper;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        $tableName = SecurityHelper::getBlockIpsTableName();
        $connection = Schema::connection(SecurityHelper::getBlockIpsConnectionName());
        $connection->dropIfExists($tableName);
        $connection->create($tableName, function (Blueprint $table) {
            $table->id('id');
            $table->string('ip')->index();
            $table->unsignedInteger('user_id')->default(0);
            $table->string('user_agent', 255)->nullable();
            $table->enum('status', ['block', 'unblock'])->default('block');
            $table->text('note')->nullable();
            $table->string('state_id')->nullable();
            $table->timestamps();
        });
    }
    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        $tableName = SecurityHelper::getBlockIpsTableName();
        $connection = Schema::connection(SecurityHelper::getBlockIpsConnectionName());
        $connection->dropIfExists($tableName);
    }
};
