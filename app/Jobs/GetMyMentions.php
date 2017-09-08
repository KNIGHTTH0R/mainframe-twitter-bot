<?php

namespace App\Jobs;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Libraries\Tweet;
use App\Models\Conversation;
use App\Models\Subscription;
use App\Models\User;
use Aubruz\Mainframe\MainframeClient;

class GetMyMentions extends TwitterJob
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
        $this->user = $user;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if($this->user->twitter_search_limit < 2){
            $this->delete();
            return;
        }

        $this->user->twitter_search_limit = $this->user->twitter_search_limit -1;
        $this->user->save();

        $this->mainframeClient      = new MainframeClient(env('BOT_SECRET'), env('MAINFRAME_API_URL'));
        $this->twitterConnection    = new TwitterOAuth(
            env("TWITTER_API_KEY"),
            env("TWITTER_API_SECRET"),
            $this->user->twitter_oauth_token,
            $this->user->twitter_oauth_token_secret
        );

        $tweets = $this->twitterConnection->get("search/tweets", [
            "q"             => '@' . $this->user->twitter_screen_name,
            "result_type"   => "recent",
            "count"         => 10,
            "tweet_mode"    => "extended",
            "since_id"      => $this->subscription->mention_since_id
        ]);

        if ($this->twitterConnection->getLastHttpCode() != 200){
            $this->delete();
            return;
        }

        $firstTweet = true;
        foreach($tweets->statuses as $tweet){

            $images = [];
            if(property_exists($tweet, "entities") && property_exists($tweet->entities, "media")){
                foreach($tweet->entities->media as $media){
                    array_push($images, [
                        "url"       => $media->media_url_https,
                        "width"     => $media->sizes->small->w,
                        "height"    => $media->sizes->small->h,
                    ]);
                }
            }
            $tweetUI = new Tweet(
                $tweet->user->name,
                $tweet->user->screen_name,
                htmlspecialchars_decode($tweet->full_text),
                $tweet->user->profile_image_url_https,
                $images
            );

            if($firstTweet){
                $this->subscription->mention_since_id = $tweet->id_str;
                $this->subscription->save();
            }

            // Send to all subscriptions where the user wants to get the tweets mentioning him/her
            // Because this job will be executed only once per minute (Because twitter api call restrictions)
            foreach($this->user->subscriptions as $subscription){
                if($subscription->get_my_timeline) {
                    $resp = $this->mainframeClient->sendMessage($subscription->conversation->mainframe_conversation_id, $tweetUI->getUIPayload(), $subscription->mainframe_subscription_id);
                }
            }

            $firstTweet = false;
        }
    }
}
