<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('conversation_user', function (Blueprint $table) {
            // Use foreignUuid for UUID foreign keys
            $table->foreignUuid('user_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('conversation_id')->constrained()->onDelete('cascade');

            // Additional columns
            $table->timestamp('joined_at')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('left_at')->nullable();
            $table->boolean('is_owner')->default(false);

            // Composite primary key
            $table->primary(['user_id', 'conversation_id']);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_user');
    }
};