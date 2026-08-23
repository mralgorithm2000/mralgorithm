<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parameter_options', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for the parameter option.');
            $table->foreignId('parameter_id')
                ->comment('Identifier of the related parameter.')
                ->constrained()
                ->cascadeOnDelete();
            $table->string('option_name')->comment('Display name of the option.');
            $table->string('option_value')->comment('Value represented by the option.');
            $table->enum('operator', ['+', '-', '%'])
                ->nullable()
                ->comment('Optional operator applied to the additional price.');
            $table->decimal('additional_price', 15, 6)
                ->nullable()
                ->comment('Optional price adjustment for the option.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parameter_options');
    }
};
