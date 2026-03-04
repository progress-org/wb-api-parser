<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use App\Models\Sale;
use App\Models\Order;
use App\Models\Stock;
use App\Models\Income;

class ImportData extends Command
{
    /**
     * Имя и сигнатура команды
     *
     * @var string
     */
    protected $signature = 'import:data 
                            {--dateFrom=2026-03-01 : Дата начала периода (Y-m-d)}
                            {--dateTo=2026-03-04 : Дата окончания периода (Y-m-d)}';

    /**
     * Описание команды
     *
     * @var string
     */
    protected $description = 'Импорт данных из WB API (sales, orders, stocks, incomes)';

    /**
     * Конфигурация API
     */
    private $apiHost = '109.73.206.144:6969';
    private $apiKey = 'E6kUTYrYwZq2tN4QEtyzsbEBk3ie';
    private $limit = 500; // Максимальный лимит API

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dateFrom = $this->option('dateFrom');
        $dateTo = $this->option('dateTo');

        $this->info('Начинаем импорт данных за период: ' . $dateFrom . ' - ' . $dateTo);
        
        // Принудительно очищаем таблицы
        $this->info('Очищаем таблицы...');
        
        try {
            DB::table('sales')->truncate();
            DB::table('orders')->truncate();
            DB::table('stocks')->truncate();
            DB::table('incomes')->truncate();
            $this->info('Таблицы очищены!');
        } catch (\Exception $e) {
            $this->error('Ошибка при очистке таблиц: ' . $e->getMessage());
            // Если truncate не работает, используем delete
            DB::table('sales')->delete();
            DB::table('orders')->delete();
            DB::table('stocks')->delete();
            DB::table('incomes')->delete();
            $this->info('Таблицы очищены через delete!');
        }
        
        // Проверяем, что таблицы действительно пустые
        $this->info('Проверка очистки:');
        $this->info('sales: ' . DB::table('sales')->count());
        $this->info('orders: ' . DB::table('orders')->count());
        $this->info('stocks: ' . DB::table('stocks')->count());
        $this->info('incomes: ' . DB::table('incomes')->count());
        
        // Импортируем все сущности
        $this->importSales($dateFrom, $dateTo);
        $this->importOrders($dateFrom, $dateTo);
        $this->importStocks($dateFrom);
        $this->importIncomes($dateFrom, $dateTo);

        $this->info('Импорт успешно завершен!');
    }

    /**
     * Импорт продаж (sales)
     */
    private function importSales($dateFrom, $dateTo)
    {
        $this->info('Импорт продаж...');
        $page = 1;
        $totalImported = 0;
        $beforeCount = DB::table('sales')->count();
        $this->info("До импорта в sales: {$beforeCount} записей");

        do {
            $response = Http::get("http://{$this->apiHost}/api/sales", [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'page' => $page,
                'limit' => $this->limit,
                'key' => $this->apiKey,
            ]);

            if ($response->failed()) {
                $this->error("Ошибка при импорте продаж (страница {$page})");
                break;
            }

            $data = $response->json();
            $salesData = $data['data'] ?? [];

            if (empty($salesData)) {
                break;
            }

            $pageCount = 0;
            foreach ($salesData as $sale) {
                try {
                    Sale::updateOrCreate(
                        ['sale_id' => $sale['sale_id']],
                        $sale
                    );
                    $pageCount++;
                    $totalImported++;
                } catch (\Exception $e) {
                    $this->error("Ошибка при сохранении: " . $e->getMessage());
                }
            }

            $this->info("Импортировано продаж: страница {$page}, записей: {$pageCount}");
            $page++;
            usleep(500000);

        } while (count($salesData) == $this->limit);

        $afterCount = DB::table('sales')->count();
        $this->info("После импорта в sales: {$afterCount} записей");
        $this->info("Импорт продаж завершен. Всего записей: {$totalImported}");
    }

    /**
     * Импорт заказов (orders) - принудительное обновление
     */
    private function importOrders($dateFrom, $dateTo)
    {
        $this->info('Импорт заказов...');
        $page = 1;
        $totalImported = 0;

        do {
            $response = Http::get("http://{$this->apiHost}/api/orders", [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'page' => $page,
                'limit' => $this->limit,
                'key' => $this->apiKey,
            ]);

            if ($response->failed()) {
                $this->error("Ошибка при импорте заказов (страница {$page})");
                break;
            }

            $data = $response->json();
            $ordersData = $data['data'] ?? [];

            if (empty($ordersData)) {
                break;
            }

            $this->info("Страница {$page}: получено " . count($ordersData) . " записей");

            foreach ($ordersData as $order) {
                try {
                    Order::updateOrCreate(
                        ['g_number' => $order['g_number']],
                        $order
                    );
                    $totalImported++;
                } catch (\Exception $e) {
                    $this->error("Ошибка при сохранении заказа {$order['g_number']}: " . $e->getMessage());
                }
            }

            $this->info("Импортировано заказов: страница {$page}, записей: " . count($ordersData));
            $page++;
            usleep(500000);

        } while (count($ordersData) == $this->limit);

        $this->info("Импорт заказов завершен. Всего записей: {$totalImported}");
    }

    /**
     * Импорт складов (stocks) - с обработкой null значений
     */
    private function importStocks($dateFrom)
    {
        $this->info('Импорт остатков складов...');
        
        // Используем несколько дат для stocks
        $dates = ['2026-03-04', '2026-03-03', '2026-03-02', '2026-03-01'];
        
        foreach ($dates as $date) {
            $this->info("Импорт stocks за {$date}...");
            $page = 1;
            $totalImported = 0;

            do {
                $response = Http::get("http://{$this->apiHost}/api/stocks", [
                    'dateFrom' => $date,
                    'page' => $page,
                    'limit' => $this->limit,
                    'key' => $this->apiKey,
                ]);

                if ($response->failed()) {
                    $this->error("Ошибка при импорте складов за {$date}: " . $response->body());
                    break;
                }

                $data = $response->json();
                $stocksData = $data['data'] ?? [];

                if (empty($stocksData)) {
                    $this->info("Нет данных за {$date} на странице {$page}");
                    break;
                }

                foreach ($stocksData as $stock) {
                    // Подготавливаем данные - заменяем null на значения по умолчанию
                    $preparedStock = [
                        'nm_id' => $stock['nm_id'] ?? 0,
                        'warehouse_name' => $stock['warehouse_name'] ?? 'Неизвестно',
                        'date' => $stock['date'] ?? $date,
                        'last_change_date' => $stock['last_change_date'] ?? $stock['date'] ?? $date,
                        'supplier_article' => $stock['supplier_article'] ?? '',
                        'tech_size' => $stock['tech_size'] ?? '',
                        'barcode' => $stock['barcode'] ?? 0,
                        'quantity' => $stock['quantity'] ?? 0,
                        'is_supply' => $stock['is_supply'] ?? false,
                        'is_realization' => $stock['is_realization'] ?? false,
                        'quantity_full' => $stock['quantity_full'] ?? 0,
                        'in_way_to_client' => $stock['in_way_to_client'] ?? 0,
                        'in_way_from_client' => $stock['in_way_from_client'] ?? 0,
                        'subject' => $stock['subject'] ?? '',
                        'category' => $stock['category'] ?? '',
                        'brand' => $stock['brand'] ?? '',
                        'sc_code' => $stock['sc_code'] ?? 0,
                        'price' => $stock['price'] ?? 0,
                        'discount' => $stock['discount'] ?? 0,
                    ];

                    try {
                        Stock::updateOrCreate(
                            [
                                'nm_id' => $preparedStock['nm_id'],
                                'warehouse_name' => $preparedStock['warehouse_name'],
                                'date' => $preparedStock['date'],
                            ],
                            $preparedStock
                        );
                        $totalImported++;
                    } catch (\Exception $e) {
                        $this->error("Ошибка при сохранении: " . $e->getMessage());
                        $this->line("Проблемные данные: " . json_encode($stock));
                    }
                }

                $this->info("{$date}: страница {$page}, записей: " . count($stocksData));
                $page++;
                usleep(500000);

            } while (count($stocksData) == $this->limit);
            
            $this->info("Импорт stocks за {$date} завершен. Записей: {$totalImported}");
        }
    }

    /**
     * Импорт доходов (incomes) - принудительное обновление
     */
    private function importIncomes($dateFrom, $dateTo)
    {
        $this->info('Импорт доходов...');
        $page = 1;
        $totalImported = 0;

        do {
            $response = Http::get("http://{$this->apiHost}/api/incomes", [
                'dateFrom' => $dateFrom,
                'dateTo' => $dateTo,
                'page' => $page,
                'limit' => $this->limit,
                'key' => $this->apiKey,
            ]);

            if ($response->failed()) {
                $this->error("Ошибка при импорте доходов (страница {$page})");
                break;
            }

            $data = $response->json();
            
            if (!isset($data['data'])) {
                $this->error("Неожиданный формат ответа: " . json_encode($data));
                break;
            }
            
            $incomesData = $data['data'];

            if (empty($incomesData)) {
                $this->info("Нет данных на странице {$page}");
                break;
            }

            $this->info("Страница {$page}: получено " . count($incomesData) . " записей");

            foreach ($incomesData as $income) {
                // Обработка специальной даты
                if (isset($income['date_close']) && $income['date_close'] === '0001-01-01') {
                    $income['date_close'] = null;
                }
                
                // Проверяем, что number не null
                if (!isset($income['number']) || $income['number'] === null) {
                    $income['number'] = '';
                }

                try {
                    Income::updateOrCreate(
                        [
                            'income_id' => $income['income_id'],
                            'nm_id' => $income['nm_id']
                        ],
                        $income
                    );
                    $totalImported++;
                } catch (\Exception $e) {
                    $this->error("Ошибка при сохранении дохода {$income['income_id']}: " . $e->getMessage());
                }
            }

            $this->info("Импортировано доходов: страница {$page}, записей: " . count($incomesData));
            $page++;
            usleep(500000);

        } while (count($incomesData) == $this->limit);

        $this->info("Импорт доходов завершен. Всего записей: {$totalImported}");
    }
}