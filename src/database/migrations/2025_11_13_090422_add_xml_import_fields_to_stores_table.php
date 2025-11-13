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
        Schema::table('stores', function (Blueprint $table) {
            $table->string('xml_import_url')->nullable()->after('logo');
            $table->boolean('xml_import_enabled')->default(false)->after('xml_import_url');
            $table->string('xml_import_schedule')->nullable()->after('xml_import_enabled'); // hourly, daily, weekly
            $table->timestamp('xml_import_last_run')->nullable()->after('xml_import_schedule');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropColumn([
                'xml_import_url',
                'xml_import_enabled',
                'xml_import_schedule',
                'xml_import_last_run',
            ]);
        });
    }
};
