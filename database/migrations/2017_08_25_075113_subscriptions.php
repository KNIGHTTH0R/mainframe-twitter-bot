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
            $table->string('hashtags')->nullable();
            $table->string('people')->nullable();
            $table->string('hashtags_since_id')->nullable();
            $table->string('people_since_id')->nullable();
            $table->string('mention_since_id')->nullable();
            $table->string('timeline_since_id')->nullable();
            $table->boolean('get_my_timeline')->default(false);
            $table->boolean('get_my_mention')->default(false);
            $table->integer('conversation_id')->unsigned();
            $table->foreign('conversation_id')->references('id')->on('conversations')->onUpdate('cascade')->onDelete('cascade');
            $table->integer('user_id')->unsigned();
            $table->foreign('user_id')->references('id')->on('users')->onUpdate('cascade')->onDelete('cascade');
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
