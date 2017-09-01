<?php

namespace App\Jobs;

use App\Models\Conversation;
use App\Models\Subscription;
use App\Models\User;

class GetHashtags extends Job
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
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //
    }
}
