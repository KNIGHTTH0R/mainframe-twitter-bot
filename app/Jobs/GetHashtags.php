<?php

namespace App\Jobs;

use Abraham\TwitterOAuth\TwitterOAuth;
use Aubruz\Mainframe\MainframeClient;

class GetHashtags extends Job
{
    /**
     * @var string
     */
    private $hashtags;


    /**
     * GetHashtags constructor.
     * @param $conversation
     * @param $subscription
     * @param $user
     * @param $hashtags
     */
    public function __construct($mainframeClient, $conversation, $subscription, $user, $hashtags)
    {
        parent::__construct($mainframeClient, $conversation, $subscription, $user);
        $this->hashtags = $hashtags;
        //
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->mainframeClient = new MainframeClient(env('BOT_SECRET'), env('MAINFRAME_API_URL'));
        $this->twitterConnection    = new TwitterOAuth(
                                            env("TWITTER_API_KEY"),
                                            env("TWITTER_API_SECRET"),
                                            $this->user->twitter_oauth_token,
                                            $this->user->twitter_oauth_token_secret
                                        );


        $response = $this->twitterConnection->get("search/tweets", [
            "q"             => urlencode("#road OR #trees"),
            "result_type"   => "recent"
        ]);

        dd($response);

        $this->mainframeClient->sendMessage($this->conversation, "Tweet");
        // Send in conversation
    }
}
