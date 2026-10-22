<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('applicant')->after('email');
            $table->string('phone')->nullable()->after('role');
            $table->string('emirates_id')->nullable()->after('phone');
            $table->string('nationality')->nullable()->after('emirates_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone', 'emirates_id', 'nationality']);
        });
    }
};
