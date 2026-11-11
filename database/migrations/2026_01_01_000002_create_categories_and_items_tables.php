<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $t) {
            $t->id();
            $t->string('name', 80)->unique();
            $t->string('slug', 100)->unique();
            $t->string('code_prefix', 10)->nullable();
            $t->smallInteger('sort_order')->default(0);
            $t->timestamps();
        });

        Schema::create('items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('category_id')->constrained('categories', 'id', 'fk_items_category');
            $t->string('code', 30)->unique();
            $t->string('name', 150);
            $t->string('summary', 255)->nullable();
            $t->text('description')->nullable();
            $t->json('specs')->nullable();
            $t->text('package_contents')->nullable();
            $t->json('checklist_template')->nullable();
            $t->string('location', 150)->nullable();
            $t->unsignedTinyInteger('max_loan_days')->default(5);
            $t->unsignedTinyInteger('max_qty_per_loan')->default(1);
            $t->unsignedSmallInteger('min_stock')->default(1);
            $t->enum('status', ['active', 'inactive'])->default('active');
            $t->string('video_url', 255)->nullable();
            $t->string('video_title', 150)->nullable();
            $t->string('video_duration', 10)->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['name', 'code'], 'idx_items_search');
            $t->index(['status', 'category_id'], 'idx_items_status');
        });

        Schema::create('item_photos', function (Blueprint $t) {
            $t->id();
            $t->foreignId('item_id')->constrained('items', 'id', 'fk_photos_item')->cascadeOnDelete();
            $t->string('path', 255);
            $t->unsignedTinyInteger('sort_order')->default(0);
            $t->boolean('is_cover')->default(false);
            $t->timestamps();
        });

        Schema::create('item_units', function (Blueprint $t) {
            $t->id();
            $t->foreignId('item_id')->constrained('items', 'id', 'fk_units_item');
            $t->string('unit_code', 40)->unique();
            $t->enum('status', ['available', 'maintenance', 'lost', 'inactive'])->default('available');
            $t->string('notes', 255)->nullable();
            $t->timestamps();
            $t->softDeletes();
            $t->index(['item_id', 'status'], 'idx_units_item_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_units');
        Schema::dropIfExists('item_photos');
        Schema::dropIfExists('items');
        Schema::dropIfExists('categories');
    }
};
