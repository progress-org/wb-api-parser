<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('incomes', function (Blueprint $table) {
            $table->id(); // Первичный ключ Laravel
            $table->bigInteger('income_id')->unique(); // ID дохода (уникальный!)
            $table->string('number'); // Номер (может быть пустым)
            $table->date('date'); // Дата поступления
            $table->date('last_change_date'); // Дата последнего изменения
            $table->string('supplier_article'); // Артикул поставщика
            $table->string('tech_size'); // Технический размер
            $table->bigInteger('barcode'); // Штрихкод
            $table->integer('quantity'); // Количество
            $table->decimal('total_price', 10, 2); // Общая цена
            $table->date('date_close'); // Дата закрытия (может быть "0001-01-01" - пустая)
            $table->string('warehouse_name'); // Название склада
            $table->bigInteger('nm_id'); // Артикул Wildberries
            
            $table->timestamps(); // created_at и updated_at
            
            // Индексы для ускорения поиска
            $table->index('date');
            $table->index('nm_id');
            $table->index('warehouse_name');
            $table->index('income_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incomes');
    }
};