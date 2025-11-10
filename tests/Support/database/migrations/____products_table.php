<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ProductsTable extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('name');
            $table->decimal('price', 10, 2)->nullable();
            $table->unsignedInteger('file_id')->nullable();
            $table->dateTime(column: 'published_at')->nullable();
            $table->timestamps();
        });
    }
}
