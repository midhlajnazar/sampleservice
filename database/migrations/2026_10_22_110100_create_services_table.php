<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('category');
            $table->string('icon')->default('file-text');
            $table->string('summary');
            $table->text('description')->nullable();
            $table->decimal('base_fee', 10, 2)->default(0);
            $table->decimal('vat_rate', 5, 2)->default(5);
            $table->unsignedSmallInteger('processing_days')->default(3);
            $table->json('form_schema');
            $table->json('document_schema');
            $table->json('eligibility')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
