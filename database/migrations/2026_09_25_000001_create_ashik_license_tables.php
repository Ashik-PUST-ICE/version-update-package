<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('ashik_purchase_codes')) {
            Schema::create('ashik_purchase_codes', function (Blueprint $table): void {
                $table->id();
                $table->string('code', 64)->unique();
                $table->string('customer_name')->nullable();
                $table->string('customer_email')->nullable();
                $table->string('status', 20)->default('active');
                $table->string('used_domain')->nullable();
                $table->timestamp('used_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('ashik_installations')) {
            Schema::create('ashik_installations', function (Blueprint $table): void {
                $table->id();
                $table->string('app_name');
                $table->string('app_url');
                $table->string('purchase_code', 64);
                $table->string('domain')->nullable();
                $table->timestamp('installed_at');
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('ashik_installations');
        Schema::dropIfExists('ashik_purchase_codes');
    }
};
