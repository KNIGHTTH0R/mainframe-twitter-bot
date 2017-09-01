<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Abraham\TwitterOAuth\TwitterOAuth;

class TwitterSearch extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitter:search';

    /**
     * @var TwitterOAuth
     */
    protected $twitterConnection;

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search via Twitter Search API';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->twitterConnection = new TwitterOAuth(env("TWITTER_API_KEY"), env("TWITTER_API_SECRET"), '2922900753-SlhkdaxJwkMhmPNhDPQ76xPxgeSYX99xcu9GRg6', '4xVVm8isaeVhvTB5wLEwiOUUwyBeSE7AYEoUiIsXs0FDo');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        //Hashtags
        $response = $this->twitterConnection->get("search/tweets", [
            "q"             => urlencode("#road OR #trees"),
            "result_type"   => "recent"
        ]);

        // Get tweets to you attention
        /*$response = $this->twitterConnection->get("search/tweets", [
            "q"             => urlencode("@aubruz"),
            "result_type"   => "recent"
        ]);*/

        //Get user's personal timeline
        //$response = $this->twitterConnection->get("statuses/home_timeline");

        //Get a user tweets
        /*$response = $this->twitterConnection->get("statuses/user_timeline",[
            "screen_name" => "sjtagg89"
        ]);*/

        //Get limits
        /*$response = $this->twitterConnection->get("application/rate_limit_status",[
            "resources" => "search,statuses,application"
        ]);*/

        // $response = $this->twitterConnection->post("account_activity/webhooks", ["url" => urlencode("https://b52d9030.ngrok.io/webhook/twitter")]);
        //return $this->respond($response);

        dd($response);
    }
}
