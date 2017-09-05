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

abstract class TwitterJob extends Job
{

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
