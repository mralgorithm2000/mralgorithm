<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_option_mappings', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for the marketplace option mapping.');
            $table->string('marketplace')
                ->index()
                ->comment('Marketplace that owns the external option.');
            $table->foreignId('parameter_option_id')
                ->comment('Identifier of the related local parameter option.')
                ->constrained()
                ->cascadeOnDelete();
            $table->unsignedBigInteger('marketplace_option_id')
                ->index()
                ->comment('Option identifier assigned by the marketplace.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_option_mappings');
    }
};
