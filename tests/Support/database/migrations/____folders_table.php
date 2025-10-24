<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FoldersTable extends Migration
{
    public function up(): void
    {
        Schema::create('folders', function (Blueprint $table): void {
            $table->increments('id');
            $table->string(column: 'name');
            $table->unsignedInteger('parent_id')->nullable();
            $table->timestamps();
        });
    }
}


