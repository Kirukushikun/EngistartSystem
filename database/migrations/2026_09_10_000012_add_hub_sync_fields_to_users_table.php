<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Where the row came from. 'manual' = an admin granted it by hand in
            // User Management; 'hub' = it was created/updated by an Access Hub
            // sync. Sync only ever touches rows it owns -- this column is the
            // guarantee. Existing rows pick up the default.
            $table->string('source')->default('manual')->after('is_active');

            // Job title, e.g. "Farm Supervisor". Hub-owned, display only, has no
            // bearing on access -- refreshed on each sync like farm/department.
            $table->string('position')->nullable()->after('department');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['source', 'position']);
        });
    }
};
