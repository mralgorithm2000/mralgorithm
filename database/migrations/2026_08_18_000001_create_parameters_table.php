<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parameters', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for the parameter.');
            $table->foreignId('goods_id')->constrained('goods')->onDelete('cascade')->comment('Foreign key referencing the good associated with the parameter.');
            $table->boolean('is_main');
            $table->string('title')->comment('Display title of the parameter.');
            $table->enum('type', [
                'text',
                'dropdown',
                'radio_button',
                'checkbox',
                'multiline_textarea',
            ])->comment('Input type used to collect the parameter value.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parameters');
    }
};
