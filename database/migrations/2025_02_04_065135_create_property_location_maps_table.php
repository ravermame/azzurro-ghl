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
        Schema::create('property_location_maps', function (Blueprint $table) {
            $table->id();
            $table->string('property_id');
            $table->string('location_id');
            $table->string('pipeline_id')->nullable();
            $table->string('pipeline_name')->nullable();
            $table->string('pipeline_stage_id')->nullable();
            $table->string('pipeline_stage_name')->nullable();
            $table->string('contact_field_id')->nullable();
            $table->string('contact_field_name')->nullable();
            $table->string('contact_property_field_id')->nullable();
            $table->string('contact_property_field_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_location_maps');
    }
};
