<?php

declare(strict_types = 1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class FilesTable extends Migration
{
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('folder_id')->nullable();
            $table->string('name');
            $table->string('path');
            $table->unsignedInteger('size')->default(0);
            $table->timestamps();
        });
    }
}
