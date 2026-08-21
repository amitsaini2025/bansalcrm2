<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Speeds completed-action type counts and listing filters on notes.
     */
    public function up(): void
    {
        if (! Schema::hasTable('notes')) {
            return;
        }

        if (Schema::hasIndex('notes', 'notes_completed_actions_idx')) {
            return;
        }

        Schema::table('notes', function (Blueprint $table) {
            $table->index(['status', 'is_action', 'type', 'task_group'], 'notes_completed_actions_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (! Schema::hasTable('notes')) {
            return;
        }

        if (! Schema::hasIndex('notes', 'notes_completed_actions_idx')) {
            return;
        }

        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex('notes_completed_actions_idx');
        });
    }
};
