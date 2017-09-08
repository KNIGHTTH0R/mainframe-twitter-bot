<?php

namespace App\Jobs;


use Abraham\TwitterOAuth\TwitterOAuth;
use App\Models\User;

class GetLimits extends Job
{
    /**
     * @var User
     */
    private $user;

    /**
     * Create a new job instance.
     *
     * @param string $user
     * @return void
     */
    public function __construct($user)
    {
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {

        $twitterConnection  = new TwitterOAuth(
            env("TWITTER_API_KEY"),
            env("TWITTER_API_SECRET"),
            $this->user->twitter_oauth_token,
            $this->user->twitter_oauth_token_secret
        );

        $response = $twitterConnection->get("application/rate_limit_status",[
            "resources" => "search,statuses,application,lists"
        ]);

        if ($twitterConnection->getLastHttpCode() != 200){
            $this->delete();
            return;
        }

        $limits = $response->resources;

        $limitsLimit = $limits->application->{"/application/rate_limit_status"}->remaining;
        $homeTimelineLimit = $limits->statuses->{"/statuses/home_timeline"}->remaining;
        $userTimelineLimit = $limits->statuses->{"/statuses/user_timeline"}->remaining;
        $searchLimit = $limits->search->{"/search/tweets"}->remaining;

        $this->user->twitter_limits_limit = $limitsLimit;
        $this->user->twitter_home_timeline_limit = $homeTimelineLimit;
        $this->user->twitter_user_timeline_limit = $userTimelineLimit;
        $this->user->twitter_search_limit = $searchLimit;
        $this->user->save();
    }
}
