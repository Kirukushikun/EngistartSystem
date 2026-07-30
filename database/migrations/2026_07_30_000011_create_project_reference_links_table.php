<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_reference_links', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_request_id')->constrained('project_requests')->cascadeOnDelete();
            $table->foreignId('added_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('url', 2048);
            $table->string('label')->nullable();
            $table->timestamps();

            $table->index('project_request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_reference_links');
    }
};
