<?php

namespace App\Jobs;

class GetHashtags extends Job
{
    /**
     * @var string
     */
    private $hashtags;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($conversation, $subscription, $user, $hashtags)
    {
        parent::__construct($conversation, $subscription, $user);
        $this->hashtags = $hashtags;
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