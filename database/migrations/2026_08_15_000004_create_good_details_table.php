<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('good_details', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for the good detail.');
            $table->foreignId('good_id')
                ->comment('Identifier of the related good.')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('good_key')->comment('Key used to identify the good detail.');
            $table->string('good_name')->comment('Display name of the good detail.');
            $table->text('good_value')->comment('Value of the good detail.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('good_details');
    }
};
