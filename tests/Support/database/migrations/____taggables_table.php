<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class TaggablesTable extends Migration
{
    public function up(): void
    {
        Schema::create('taggables', function (Blueprint $table) {
            $table->increments('tag_id');
            $table->string('taggable_id');
            $table->string('taggable_type');
            $table->timestamps();
        });
    }
}
