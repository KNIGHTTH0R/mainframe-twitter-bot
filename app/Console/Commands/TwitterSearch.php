<?php

namespace App\Console\Commands;

use App\Jobs\GetList;
use Illuminate\Console\Command;
use App\Models\User;
use App\Jobs\GetSearch;
use App\Jobs\GetLimits;
use App\Jobs\GetMyMentions;
use App\Jobs\GetUserTimeline;
use App\Jobs\GetMyTimeline;
use Laravel\Lumen\Routing\ProvidesConvenienceMethods;

class TwitterSearch extends Command
{
    use ProvidesConvenienceMethods;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'twitter:search';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Search via Twitter Search API';

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
        $users = User::with('subscriptions.conversation', 'twitterlists')->get();

        foreach($users as $user){

            $callToMyMention = false;
            $callToMyTimeline = false;
            $callToGetLists = false;

            // Refresh rate limits
            $this->dispatch(new GetLimits($user));

            foreach($user->subscriptions as $subscription){

                // Get search
                if($subscription->search != ''){
                    $this->dispatch(new GetSearch(
                        $subscription->conversation,
                        $subscription,
                        $user
                    ));
                }

                // Get other users timeline
                if($subscription->people != ''){
                    $this->dispatch(new GetUserTimeline(
                        $subscription->conversation,
                        $subscription,
                        $user
                    ));
                }

                // Get lists
                if(!$callToGetLists && $user->twitterlists){
                    $this->dispatch(new GetList(
                       $subscription->conversation,
                        $subscription,
                        $user
                    ));
                    $callToGetLists = true;
                }

                // Get tweets that mention the user's name
                if(!$callToMyMention && $subscription->get_my_mention){
                    $this->dispatch(new GetMyMentions(
                        $subscription->conversation,
                        $subscription,
                        $user
                    ));
                    $callToMyMention = true;
                }

                // Get tweets from the user's timeline
                if(!$callToMyTimeline && $subscription->get_my_timeline){
                    $this->dispatch(new GetMyTimeline(
                        $subscription->conversation,
                        $subscription,
                        $user
                    ));
                    $callToMyTimeline = true;
                }

            }
        }
        $this->info("Job done!");

        // ACCOUNT ACTIVITY API - Register webhook
        // $response = $this->twitterConnection->post("account_activity/webhooks", ["url" => urlencode("https://b52d9030.ngrok.io/webhook/twitter")]);
    }
}
