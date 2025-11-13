<?php

namespace App\Jobs;

use App\Modules\Import\Services\ProductImportService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ImportProductsFromXml implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public int $backoff = 60;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $xmlUrl,
        public int $storeId,
        public ?int $tenantId = null
    ) {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(ProductImportService $importService): void
    {
        Log::info('Starting XML product import', [
            'xml_url' => $this->xmlUrl,
            'store_id' => $this->storeId,
            'tenant_id' => $this->tenantId,
        ]);

        try {
            $results = $importService->importFromUrl(
                $this->xmlUrl,
                $this->storeId,
                $this->tenantId
            );

            Log::info('XML product import completed', [
                'store_id' => $this->storeId,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('XML product import failed', [
                'xml_url' => $this->xmlUrl,
                'store_id' => $this->storeId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw to trigger retry mechanism
        }
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('XML product import job failed permanently', [
            'xml_url' => $this->xmlUrl,
            'store_id' => $this->storeId,
            'error' => $exception->getMessage(),
        ]);
    }
}
