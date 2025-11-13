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
        Schema::table('users', function (Blueprint $table) {
            // Check if column exists, if not add it
            if (!Schema::hasColumn('users', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            }
            // Add foreign key constraint if it doesn't exist
            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            // Add index if it doesn't exist
            if (!$this->hasIndex('users', 'tenant_id')) {
                $table->index('tenant_id');
            }
        });
    }
    
    /**
     * Check if index exists
     */
    private function hasIndex($table, $column)
    {
        $connection = Schema::getConnection();
        $databaseName = $connection->getDatabaseName();
        $indexes = $connection->select("SHOW INDEX FROM `{$table}` WHERE Column_name = '{$column}'");
        return count($indexes) > 0;
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['tenant_id']);
            $table->dropIndex(['tenant_id']);
            $table->dropColumn('tenant_id');
        });
    }
};
