<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('external_user_id')->nullable()->unique()->after('id');
            $table->string('farm')->nullable()->after('role');
            $table->string('department')->nullable()->after('farm');
            $table->boolean('is_active')->default(true)->after('department');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropUnique(['external_user_id']);
            $table->dropColumn(['external_user_id', 'farm', 'department', 'is_active']);
        });
    }
};
