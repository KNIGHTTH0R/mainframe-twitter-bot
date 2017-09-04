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
    protected $mainframeClient = null;

    /**
     * @var TwitterOAuth
     */
    protected $twitterConnection = null;

    /**
     * @var User
     */
    protected $user;

    /**
     * Job constructor.
     * @param Conversation $conversation
     * @param Subscription $subscription
     * @param User $user
     */
    public function __construct($conversation, $subscription, $user)
    {
        $this->conversation         = $conversation;
        $this->subscription         = $subscription;
        $this->user                 = $user;
    }
}
