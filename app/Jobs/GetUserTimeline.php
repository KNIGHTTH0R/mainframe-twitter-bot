<?php

namespace App\Jobs;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Models\User;
use Aubruz\Mainframe\MainframeClient;

class GetUserTimeline extends Job
{

    /**
     * Create a new job instance.
     *
     * @param string $screenName
     * @return void
     */
    public function __construct($conversation, $subscription, $user)
    {
        parent::__construct($conversation, $subscription, $user);
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

        $response = $this->twitterConnection->get("statuses/user_timeline",[
            "screen_name" => "sjtagg89"
        ]);

    }
}
