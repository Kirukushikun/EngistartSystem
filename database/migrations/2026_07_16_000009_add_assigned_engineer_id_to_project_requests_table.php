<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_requests', function (Blueprint $table) {
            $table->foreignId('assigned_engineer_id')->nullable()->after('current_owner_id')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_engineer_id');
        });
    }
};
