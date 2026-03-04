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
        Schema::create('orders', function (Blueprint $table) {
            $table->id(); // Первичный ключ Laravel
            $table->string('g_number'); // Номер заказа
            $table->dateTime('date'); // Дата и время заказа
            $table->date('last_change_date'); // Дата последнего изменения
            $table->string('supplier_article'); // Артикул поставщика
            $table->string('tech_size'); // Технический размер
            $table->bigInteger('barcode'); // Штрихкод
            $table->decimal('total_price', 10, 2); // Общая цена
            $table->integer('discount_percent'); // Процент скидки
            $table->string('warehouse_name'); // Название склада
            $table->string('oblast'); // Область/регион
            $table->bigInteger('income_id'); // ID дохода
            $table->string('odid')->unique(); // ODID (уникальный идентификатор заказа)
            $table->bigInteger('nm_id'); // Артикул Wildberries
            $table->string('subject'); // Предмет
            $table->string('category'); // Категория
            $table->string('brand'); // Бренд
            $table->boolean('is_cancel'); // Отменен ли заказ
            $table->dateTime('cancel_dt')->nullable(); // Дата отмены (может быть null)
            
            $table->timestamps(); // created_at и updated_at
            
            // Индексы для ускорения поиска
            $table->index('date');
            $table->index('nm_id');
            $table->index('warehouse_name');
            $table->index('odid');
            $table->index('is_cancel');
            
            // Уникальное поле - скорее всего odid или g_number
            // В данном случае odid выглядит как уникальный идентификатор заказа
            $table->unique('odid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};