<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatHistoriesTable extends Migration
{
    public function up()
    {
        Schema::create('chat_histories', function (Blueprint $table) {
            $table->id();
            $table->text('message');       // Message content
            $table->boolean('is_user');    // True if message is from user, false if from bot
            $table->timestamps();          // Created_at and updated_at timestamps
            $table->string('session_id')->nullable()->index(); // Unique ID for each chat session
        });
    }

    public function down()
    {
        Schema::dropIfExists('chat_histories');
    }
}
