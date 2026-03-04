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
        Schema::create('sales', function (Blueprint $table) {
            $table->id(); // Первичный ключ Laravel
            $table->string('g_number'); // Номер заказа
            $table->date('date'); // Дата продажи (только дата)
            $table->dateTime('last_change_date'); // Дата последнего изменения
            $table->string('supplier_article'); // Артикул поставщика
            $table->string('tech_size'); // Технический размер
            $table->bigInteger('barcode'); // Штрихкод
            $table->decimal('total_price', 10, 2); // Общая цена
            $table->integer('discount_percent'); // Процент скидки
            $table->boolean('is_supply'); // Поставка?
            $table->boolean('is_realization'); // Реализация?
            $table->string('promo_code_discount')->nullable(); // Скидка по промокоду (может быть null)
            $table->string('warehouse_name'); // Название склада
            $table->string('country_name'); // Страна
            $table->string('oblast_okrug_name'); // Область/округ
            $table->string('region_name'); // Регион
            $table->bigInteger('income_id'); // ID дохода
            $table->string('sale_id')->unique(); // Уникальный ID продажи (сделаем уникальным!)
            $table->string('odid')->nullable(); // ODID (может быть null)
            $table->string('spp'); // СПП (строка, т.к. может содержать не только числа)
            $table->decimal('for_pay', 10, 2); // К оплате
            $table->decimal('finished_price', 10, 2); // Итоговая цена
            $table->decimal('price_with_disc', 10, 2); // Цена со скидкой
            $table->bigInteger('nm_id'); // Артикул Wildberries
            $table->string('subject'); // Предмет
            $table->string('category'); // Категория
            $table->string('brand'); // Бренд
            $table->boolean('is_storno')->nullable(); // Сторно (может быть null)
            
            $table->timestamps(); // created_at и updated_at
            
            // Рекомендуемые индексы для ускорения поиска
            $table->index('date');
            $table->index('nm_id');
            $table->index('warehouse_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};