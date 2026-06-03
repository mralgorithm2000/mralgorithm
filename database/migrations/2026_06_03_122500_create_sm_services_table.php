<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sm_services', function (Blueprint $table) {
            $table->id();
            $table->uuid('random_id');
            $table->string('api_id');
            $table->enum('type',['instagram_like','instagram_follower','instagram_view']);
            $table->enum('origin',['followeran']);
            $table->string('name');
            $table->enum('sm',['instagram','telegram']);
            $table->string('min')->default(100);
            $table->string('max');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sm_services');
    }
};
