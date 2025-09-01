<?php

use App\Enums\JobStatus;
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
        Schema::create('sync_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('legal_entity_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('status', JobStatus::values())->default(JobStatus::PENDING->value);
            $table->string('entity_type')->nullable();
            $table->integer('page')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sync_jobs');
    }
};
