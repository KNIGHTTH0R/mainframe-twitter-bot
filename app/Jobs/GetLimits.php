<?php

namespace App\Jobs;


class GetLimits extends Job
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
        $response = $this->twitterConnection->get("application/rate_limit_status",[
            "resources" => "search,statuses,application"
        ]);

    }
}
