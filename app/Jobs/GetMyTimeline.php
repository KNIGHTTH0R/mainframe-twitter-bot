<?php

namespace App\Jobs;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Libraries\Tweet;
use App\Models\Conversation;
use App\Models\Subscription;
use App\Models\User;
use Aubruz\Mainframe\MainframeClient;

class GetMyTimeline extends TwitterJob
{

    /**
     * GetMyTimeline constructor.
     * @param Conversation $conversation
     * @param Subscription $subscription
     * @param User $user
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

        if($this->user->twitter_home_timeline_limit < 2){
            $this->delete();
            return;
        }

        $this->user->twitter_home_timeline_limit = $this->user->twitter_home_timeline_limit -1;
        $this->user->save();

        $this->mainframeClient      = new MainframeClient(env('BOT_SECRET'), env('MAINFRAME_API_URL'));
        $this->twitterConnection    = new TwitterOAuth(
            env("TWITTER_API_KEY"),
            env("TWITTER_API_SECRET"),
            $this->user->twitter_oauth_token,
            $this->user->twitter_oauth_token_secret
        );

        $tweets = $this->twitterConnection->get("statuses/home_timeline", [
            "count"         => 10,
            "tweet_mode"    => "extended",
            "since_id"      => $this->subscription->timeline_since_id
        ]);

        if ($this->twitterConnection->getLastHttpCode() != 200){
            $this->delete();
            return;
        }

        $firstTweet = true;
        foreach($tweets as $tweet){

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
                $tweet->id_str,
                'url',
                $tweet->user->name,
                $tweet->user->screen_name,
                htmlspecialchars_decode($tweet->full_text),
                $tweet->user->profile_image_url_https,
                $images
            );

            if($firstTweet){
                $this->subscription->timeline_since_id = $tweet->id_str;
                $this->subscription->save();
            }

            // Send to all subscriptions where the user wants to get his/her timeline
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
