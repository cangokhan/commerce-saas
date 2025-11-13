<?php

use App\Jobs\ImportProductsFromXml;
use App\Modules\Store\Models\Store;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

// Scheduled XML import tasks
// You can configure these in .env file:
// XML_IMPORT_STORE_1_URL=https://example.com/products.xml
// XML_IMPORT_STORE_1_ID=1
// XML_IMPORT_STORE_1_SCHEDULE=hourly|daily|weekly

// Scheduled XML import from stores table
Schedule::call(function () {
    $stores = Store::whereNotNull('xml_import_url')
        ->where('xml_import_enabled', true)
        ->get();

    foreach ($stores as $store) {
        if ($store->xml_import_url && $store->xml_import_schedule) {
            ImportProductsFromXml::dispatch(
                $store->xml_import_url,
                $store->id,
                $store->tenant_id
            );
            
            // Update last run time
            $store->update(['xml_import_last_run' => now()]);
        }
    }
})->hourly(); // Default: hourly, can be changed per store

// Alternative: Use environment variables for scheduled imports
if (env('XML_IMPORT_ENABLED', false)) {
    $importStores = [];
    
    // Parse store configurations from env
    for ($i = 1; $i <= 10; $i++) {
        $url = env("XML_IMPORT_STORE_{$i}_URL");
        $storeId = env("XML_IMPORT_STORE_{$i}_ID");
        $schedule = env("XML_IMPORT_STORE_{$i}_SCHEDULE", 'hourly');
        
        if ($url && $storeId) {
            $importStores[] = [
                'url' => $url,
                'store_id' => $storeId,
                'schedule' => $schedule,
            ];
        }
    }

    foreach ($importStores as $config) {
        $scheduleMethod = match($config['schedule']) {
            'hourly' => 'hourly',
            'daily' => 'daily',
            'weekly' => 'weekly',
            'everyThirtyMinutes' => 'everyThirtyMinutes',
            default => 'hourly',
        };

        Schedule::call(function () use ($config) {
            \App\Jobs\ImportProductsFromXml::dispatch(
                $config['url'],
                $config['store_id']
            );
        })->{$scheduleMethod}();
    }
}
