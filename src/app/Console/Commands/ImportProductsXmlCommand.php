<?php

namespace App\Console\Commands;

use App\Modules\Import\Jobs\ImportProductsFromXml;
use Illuminate\Console\Command;

class ImportProductsXmlCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:products-xml 
                            {--url= : XML URL to import from}
                            {--tenant= : Tenant ID to import products for}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import products from external XML service';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $xmlUrl = $this->option('url') ?? env('XML_IMPORT_URL');
        $tenantId = $this->option('tenant');

        if (!$xmlUrl) {
            $this->error('XML URL is required. Set XML_IMPORT_URL in .env or use --url option.');
            return 1;
        }

        $this->info('Starting XML import from: ' . $xmlUrl);

        // Dispatch job to queue
        ImportProductsFromXml::dispatch($xmlUrl, $tenantId);

        $this->info('XML import job dispatched to queue successfully!');
        $this->info('Check queue worker logs for progress.');

        return 0;
    }
}
