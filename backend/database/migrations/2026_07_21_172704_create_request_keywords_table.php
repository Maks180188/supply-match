<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sourcing_request_id')->constrained()->cascadeOnDelete();
            $table->string('keyword', 100);
            $table->timestamps();

            $table->unique(['sourcing_request_id', 'keyword']);
            $table->index('keyword');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_keywords');
    }
};
