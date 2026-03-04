<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
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

        $this->info('Начинаем импорт данных...');
        
        // Импортируем все сущности
        $this->importSales($dateFrom, $dateTo);
        $this->importOrders($dateFrom, $dateTo);
        $this->importStocks($dateFrom); // Для stocks нужен только dateFrom
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

            foreach ($salesData as $sale) {
                Sale::updateOrCreate(
                    ['sale_id' => $sale['sale_id']], // Уникальный ключ
                    $sale
                );
                $totalImported++;
            }

            $this->info("Импортировано продаж: страница {$page}, записей: " . count($salesData));
            $page++;
            
            // Небольшая задержка, чтобы не нагружать API
            usleep(500000); // 0.5 секунды

        } while (count($salesData) == $this->limit);

        $this->info("Импорт продаж завершен. Всего записей: {$totalImported}");
    }

    /**
     * Импорт заказов (orders)
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

            foreach ($ordersData as $order) {
                Order::updateOrCreate(
                    ['odid' => $order['odid']], // Уникальный ключ
                    $order
                );
                $totalImported++;
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
        
        // Проверяем дату
        $today = now()->format('Y-m-d');
        $yesterday = now()->subDay()->format('Y-m-d');
        
        if ($dateFrom < $yesterday) {
            $this->warn("Дата {$dateFrom} слишком старая для stocks. Используем сегодняшнюю дату: {$today}");
            $dateFrom = $today;
        }
        
        $page = 1;
        $totalImported = 0;

        do {
            $response = Http::get("http://{$this->apiHost}/api/stocks", [
                'dateFrom' => $dateFrom,
                'page' => $page,
                'limit' => $this->limit,
                'key' => $this->apiKey,
            ]);

            if ($response->failed()) {
                $this->error("Ошибка при импорте складов: " . $response->body());
                break;
            }

            $data = $response->json();
            
            if (!isset($data['data'])) {
                $this->error("Неожиданный формат ответа");
                break;
            }
            
            $stocksData = $data['data'];

            if (empty($stocksData)) {
                break;
            }

            foreach ($stocksData as $stock) {
                // Подготавливаем данные - заменяем null на значения по умолчанию
                $preparedStock = [
                    'nm_id' => $stock['nm_id'],
                    'warehouse_name' => $stock['warehouse_name'] ?? 'Неизвестно',
                    'date' => $stock['date'] ?? $today,
                    'last_change_date' => $stock['last_change_date'] ?? $stock['date'] ?? $today,
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

            $this->info("Импортировано складов: страница {$page}, записей: " . count($stocksData));
            $page++;
            usleep(500000);

        } while (count($stocksData) == $this->limit);

        $this->info("Импорт складов завершен. Всего записей: {$totalImported}");
    }

    /**
     * Импорт доходов (incomes)
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
            $incomesData = $data['data'] ?? [];

            if (empty($incomesData)) {
                break;
            }

            foreach ($incomesData as $income) {
                // Обработка специальной даты
                if (isset($income['date_close']) && $income['date_close'] === '0001-01-01') {
                    $income['date_close'] = null;
                }

                Income::updateOrCreate(
                    ['income_id' => $income['income_id']], // Уникальный ключ
                    $income
                );
                $totalImported++;
            }

            $this->info("Импортировано доходов: страница {$page}, записей: " . count($incomesData));
            $page++;
            usleep(500000);

        } while (count($incomesData) == $this->limit);

        $this->info("Импорт доходов завершен. Всего записей: {$totalImported}");
    }
}