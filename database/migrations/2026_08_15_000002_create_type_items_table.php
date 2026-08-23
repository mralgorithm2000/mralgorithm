<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('type_items', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for the type item.');
            $table->foreignId('type_id')
                ->comment('Identifier of the related type.')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('type_key')->unique()->comment('Unique key used to identify the type item.');
            $table->string('type_name')->comment('Display name of the type item.');
            $table->enum('type', ['text', 'dropdown', 'multiple_choice'])
                ->comment('Field type: text, dropdown, or multiple choice.');
            $table->json('options')->nullable()->comment('Options available for dropdown or multiple-choice fields.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('type_items');
    }
};
