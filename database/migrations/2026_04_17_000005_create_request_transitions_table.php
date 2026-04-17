<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_transitions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_request_id')->constrained('project_requests')->cascadeOnDelete();
            $table->foreignId('acted_by_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('acted_by_role')->nullable()->index();
            $table->string('action');
            $table->string('from_status')->nullable()->index();
            $table->string('to_status')->index();
            $table->string('from_step')->nullable()->index();
            $table->string('to_step')->nullable()->index();
            $table->string('from_owner_role')->nullable()->index();
            $table->string('to_owner_role')->nullable()->index();
            $table->foreignId('to_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_rework')->default(false)->index();
            $table->boolean('is_exception_path')->default(false)->index();
            $table->boolean('is_terminal')->default(false)->index();
            $table->text('remarks')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('acted_at')->nullable()->index();
            $table->timestamps();

            $table->index(['project_request_id', 'acted_at']);
            $table->index(['project_request_id', 'to_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_transitions');
    }
};
