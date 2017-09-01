<?php

namespace App\Jobs;

use App\Models\User;

class GetHashtags extends Job
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
        //
    }
}
