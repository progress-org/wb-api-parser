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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id(); // Первичный ключ Laravel
            $table->date('date'); // Дата отчета
            $table->date('last_change_date'); // Дата последнего изменения
            $table->string('supplier_article'); // Артикул поставщика
            $table->string('tech_size'); // Технический размер
            $table->bigInteger('barcode'); // Штрихкод
            $table->integer('quantity'); // Количество
            $table->boolean('is_supply'); // Поставка?
            $table->boolean('is_realization'); // Реализация?
            $table->integer('quantity_full'); // Полное количество
            $table->string('warehouse_name'); // Название склада
            $table->integer('in_way_to_client'); // В пути к клиенту
            $table->integer('in_way_from_client'); // В пути от клиента
            $table->bigInteger('nm_id'); // Артикул Wildberries
            $table->string('subject'); // Предмет
            $table->string('category'); // Категория
            $table->string('brand'); // Бренд
            $table->bigInteger('sc_code'); // SC код
            $table->decimal('price', 10, 2); // Цена
            $table->integer('discount'); // Скидка
            
            $table->timestamps(); // created_at и updated_at
            
            // Индексы для ускорения поиска
            $table->index('date');
            $table->index('nm_id');
            $table->index('warehouse_name');
            $table->index('barcode');
            
            // У stocks нет очевидного уникального поля, 
            // но комбинация nm_id + warehouse_name + date должна быть уникальной
            $table->unique(['nm_id', 'warehouse_name', 'date'], 'stock_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};