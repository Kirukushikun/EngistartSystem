<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_requests', function (Blueprint $table) {
            $table->string('budget_category')->nullable()->after('request_type');
            $table->date('project_start_date')->nullable()->after('date_needed');
            $table->date('project_completion_date')->nullable()->after('project_start_date');
        });
    }

    public function down(): void
    {
        Schema::table('project_requests', function (Blueprint $table) {
            $table->dropColumn(['budget_category', 'project_start_date', 'project_completion_date']);
        });
    }
};
