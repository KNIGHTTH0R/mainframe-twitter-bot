<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Abraham\TwitterOAuth\TwitterOAuth;
use Aubruz\Mainframe\MainframeClient;

abstract class Job implements ShouldQueue
{
    /*
    |--------------------------------------------------------------------------
    | Queueable Jobs
    |--------------------------------------------------------------------------
    |
    | This job base class provides a central location to place any logic that
    | is shared across all of your jobs. The trait included with the class
    | provides access to the "queueOn" and "delay" queue helper methods.
    |
    */

    use InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @var Conversation
     */
    protected $conversation;

    /**
     * @var Subscription
     */
    protected $subscription;

    /**
     * @var MainframeClient
     */
    protected $mainframeClient;

    /**
     * @var TwitterOAuth
     */
    protected $twitterConnection;

    /**
     * @var User
     */
    protected $user;

    /**
     * Job constructor.
     * @param $conversation
     * @param $subscription
     */
    public function __construct($conversation, $subscription, $user)
    {
        $this->conversation         = $conversation;
        $this->subscription         = $subscription;
        $this->user                 = $user;
        $this->mainframeClient      = new MainframeClient(env('BOT_SECRET'), env('MAINFRAME_API_URL'));
        $this->twitterConnection    = new TwitterOAuth(
                                            env("TWITTER_API_KEY"),
                                            env("TWITTER_API_SECRET"),
                                            $user->twitter_oauth_token,
                                            $user->twitter_oauth_token_secret
                                        );
    }
}
