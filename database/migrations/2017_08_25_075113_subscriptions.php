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
            $table->string('label');
            $table->string('mainframe_subscription_id');
            $table->string('search')->nullable();
            $table->string('people')->nullable();
            $table->string('hashtags_since_id')->default('1');
            $table->string('people_since_id')->default('1');
            $table->string('mention_since_id')->default('1');
            $table->string('timeline_since_id')->default('1');
            $table->boolean('get_my_timeline')->default(false);
            $table->boolean('get_my_mention')->default(false);
            $table->boolean('get_people_retweets')->default(false);
            $table->boolean('get_people_replies')->default(false);
            $table->integer('conversation_id')->unsigned();
            $table->foreign('conversation_id')->references('id')->on('conversations')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('twitter_list_id')->unsigned()->nullable();
            $table->foreign('twitter_list_id')->references('id')->on('twitter_lists')->onUpdate('cascade')->onDelete('set null');
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
        Schema::dropIfExists('subscriptions');
    }
}
