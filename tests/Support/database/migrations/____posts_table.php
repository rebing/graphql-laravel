<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class PostsTable extends Migration
{
    public function up(): void
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->string('title');
            $table->string('body')->nullable();
            $table->integer('user_id')->nullable();
            $table->text('properties')->nullable();
            $table->boolean('flag')->default('false');
            $table->dateTime('published_at')->nullable();
            $table->timestamps();
        });
    }
}
