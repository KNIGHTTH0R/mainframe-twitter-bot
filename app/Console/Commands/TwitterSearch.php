<?php

namespace App\Console\Commands;

use App\Models\User;
use Aubruz\Mainframe\MainframeClient;
use Illuminate\Console\Command;
use Abraham\TwitterOAuth\TwitterOAuth;
use App\Jobs\GetHashtags;
use App\Jobs\GetLimits;
use App\Jobs\GetMyMentions;
use App\Jobs\GetUserTimeline;
use App\Jobs\GetMyTimeline;
use Illuminate\Support\Facades\Queue;
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
     * @var TwitterOAuth
     */
    protected $twitterConnection;

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
        $this->twitterConnection = new TwitterOAuth(env("TWITTER_API_KEY"), env("TWITTER_API_SECRET"), '2922900753-SlhkdaxJwkMhmPNhDPQ76xPxgeSYX99xcu9GRg6', '4xVVm8isaeVhvTB5wLEwiOUUwyBeSE7AYEoUiIsXs0FDo');
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $users = User::with('subscriptions.conversation')->get();

        foreach($users as $user){

            //Refresh rate limit

            foreach($user->subscriptions as $subscription){

                if($subscription->hashtags != ''){
                    $this->dispatch(new GetHashtags(
                        $subscription->conversation,
                        $subscription,
                        $user,
                        $subscription->hashtags
                    ));
                }
                if($subscription->people != ''){

                }
                $conversationID = $subscription->conversation->mainframe_conversation_id;
                $this->info($conversationID);
            }
        }

        // $response = $this->twitterConnection->post("account_activity/webhooks", ["url" => urlencode("https://b52d9030.ngrok.io/webhook/twitter")]);
        //return $this->respond($response);

       // dd($response);
    }
}
