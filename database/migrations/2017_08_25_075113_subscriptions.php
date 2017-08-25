<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Subscriptions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('conversation_id')->unsigned();
            $table->foreign('conversation_id')->references('id')->on('conversations')->onUpdate('cascade')->onDelete('cascade');
            $table->string('label');
            $table->string('hashtags')->nullable();
            $table->string('people')->nullable();
            $table->string('twitter_oauth_token')->nullable();
            $table->string('twitter_oauth_token_secret')->nullable();
            $table->string('twitter_user_id')->nullable();
            $table->string('twitter_screen_name')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('subscriptions');
    }
}
