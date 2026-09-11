<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hub_connections', function (Blueprint $table) {
            $table->id();
            // Issued by POST /api/v1/enroll. There is only ever one row --
            // App\Models\HubConnection::replace() enforces that.
            $table->string('client_id');
            $table->text('client_secret'); // encrypted at rest via the model cast
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hub_connections');
    }
};
