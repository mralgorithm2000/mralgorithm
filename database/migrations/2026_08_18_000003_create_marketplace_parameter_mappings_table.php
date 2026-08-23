<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_parameter_mappings', function (Blueprint $table) {
            $table->id()->comment('Unique identifier for the marketplace parameter mapping.');
            $table->string('marketplace')
                ->index()
                ->comment('Marketplace that owns the external parameter.');
            $table->unsignedBigInteger('marketplace_parameter_id')
                ->index()
                ->comment('Parameter identifier assigned by the marketplace.');
            $table->foreignId('parameter_id')
                ->comment('Identifier of the related local parameter.')
                ->constrained()
                ->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_parameter_mappings');
    }
};
