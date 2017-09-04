<?php

namespace App\Jobs;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Models\Conversation;
use App\Models\Subscription;
use App\Models\User;
use Aubruz\Mainframe\MainframeClient;

class GetMyMentions extends Job
{
    /**
     * Create a new job instance.
     *
     * @param User $user
     * @return void
     */
    public function __construct(Conversation $conversation, Subscription $subscription, User $user)
    {
        parent::__construct($conversation, $subscription, $user);
        $this->user = $user;
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
            "q"             => urlencode("@aubruz"),
            "result_type"   => "recent"
        ]);
    }
}
