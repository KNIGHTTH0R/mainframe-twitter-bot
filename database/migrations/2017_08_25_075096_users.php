<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class Users extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('mainframe_user_id')->unique();
            $table->string('twitter_oauth_request_token')->nullable();
            $table->string('twitter_oauth_token')->nullable();
            $table->string('twitter_oauth_token_secret')->nullable();
            $table->string('twitter_user_id')->nullable();
            $table->string('twitter_screen_name')->nullable();
            $table->integer('twitter_home_timeline_limit')->default(0);
            $table->integer('twitter_user_timeline_limit')->default(0);
            $table->integer('twitter_search_limit')->default(0);
            $table->integer('twitter_get_lists_limit')->default(0);
            $table->integer('twitter_show_list_limit')->default(0);
            $table->integer('twitter_limits_limit')->default(0);
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
        Schema::dropIfExists('users');
    }
}
