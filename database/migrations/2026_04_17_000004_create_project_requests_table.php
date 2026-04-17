<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('requestor_id')->constrained('users')->cascadeOnDelete();
            $table->string('requestor_role');
            $table->string('current_status')->index();
            $table->string('current_step')->nullable()->index();
            $table->string('current_owner_role')->nullable()->index();
            $table->foreignId('current_owner_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_late')->default(false)->index();
            $table->boolean('is_exception_flow')->default(false)->index();
            $table->string('exception_status')->nullable()->index();
            $table->string('title');
            $table->string('request_type');
            $table->string('farm_name')->nullable();
            $table->text('purpose')->nullable();
            $table->date('date_needed');
            $table->date('chick_in_date')->nullable();
            $table->string('capacity')->nullable();
            $table->text('description');
            $table->date('preferred_meeting_date')->nullable();
            $table->time('preferred_meeting_time')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('last_transitioned_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->longText('latest_remarks')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['current_status', 'current_owner_role']);
            $table->index(['requestor_id', 'current_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_requests');
    }
};
