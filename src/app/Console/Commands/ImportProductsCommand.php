<?php

namespace App\Console\Commands;

use App\Jobs\ImportProductsFromXml;
use App\Modules\Store\Models\Store;
use Illuminate\Console\Command;

class ImportProductsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'products:import-xml 
                            {--url= : XML URL to import from}
                            {--store= : Store ID to import products to}
                            {--tenant= : Tenant ID (optional)}
                            {--queue : Run import in queue}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from XML URL';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $xmlUrl = $this->option('url');
        $storeId = $this->option('store');
        $tenantId = $this->option('tenant');
        $useQueue = $this->option('queue');

        if (!$xmlUrl) {
            $this->error('XML URL is required. Use --url option.');
            return Command::FAILURE;
        }

        if (!$storeId) {
            $this->error('Store ID is required. Use --store option.');
            return Command::FAILURE;
        }

        // Validate store exists
        $store = Store::find($storeId);
        if (!$store) {
            $this->error("Store with ID {$storeId} not found.");
            return Command::FAILURE;
        }

        if (!$tenantId) {
            $tenantId = $store->tenant_id;
        }

        $this->info("Starting product import from: {$xmlUrl}");
        $this->info("Store: {$store->name} (ID: {$storeId})");
        $this->info("Tenant ID: {$tenantId}");

        if ($useQueue) {
            ImportProductsFromXml::dispatch($xmlUrl, $storeId, $tenantId);
            $this->info('Import job queued successfully.');
        } else {
            $importService = app(\App\Modules\Import\Services\ProductImportService::class);
            $results = $importService->importFromUrl($xmlUrl, $storeId, $tenantId);

            $this->info("Import completed!");
            $this->info("Success: {$results['success']}");
            $this->info("Failed: {$results['failed']}");
            $this->info("Skipped: {$results['skipped']}");

            if (!empty($results['errors'])) {
                $this->warn('Errors:');
                foreach ($results['errors'] as $error) {
                    $this->line("  - {$error}");
                }
            }
        }

        return Command::SUCCESS;
    }
}
