<?php
// database/migrations/2026_03_04_xxxxxx_modify_date_close_in_incomes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('incomes', function (Blueprint $table) {
            // Делаем поле nullable
            $table->date('date_close')->nullable()->change();
        });
    }

    public function down()
    {
        Schema::table('incomes', function (Blueprint $table) {
            $table->date('date_close')->nullable(false)->change();
        });
    }
};