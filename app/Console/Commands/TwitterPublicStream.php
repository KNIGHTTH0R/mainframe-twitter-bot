<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use TwitterStreamingApi;


class TwitterPublicStream extends Command
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitter:public-stream';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Receive twitter feed via stream API.';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        TwitterStreamingApi::publicStream()
            ->whenHears('#laravel', function(array $tweet) {
                $this->info( "{$tweet['user']['screen_name']} tweeted {$tweet['text']}");
            })
            ->startListening();
    }
}
