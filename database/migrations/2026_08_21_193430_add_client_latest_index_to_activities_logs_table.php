<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Speeds latest-activity-per-client lookups
     * (DISTINCT ON / ROW_NUMBER ordered by created_at DESC, id DESC).
     */
    public function up(): void
    {
        if (! Schema::hasTable('activities_logs')) {
            return;
        }

        if (Schema::hasIndex('activities_logs', 'activities_logs_client_latest_idx')) {
            return;
        }

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('CREATE INDEX activities_logs_client_latest_idx ON activities_logs (client_id, created_at DESC, id DESC)');

            return;
        }

        Schema::table('activities_logs', function (Blueprint $table) {
            $table->index(['client_id', 'created_at', 'id'], 'activities_logs_client_latest_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('activities_logs')) {
            return;
        }

        if (! Schema::hasIndex('activities_logs', 'activities_logs_client_latest_idx')) {
            return;
        }

        Schema::table('activities_logs', function (Blueprint $table) {
            $table->dropIndex('activities_logs_client_latest_idx');
        });
    }
};
