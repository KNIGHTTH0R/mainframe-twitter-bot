<?php

namespace App\Jobs;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Libraries\Tweet;
use Aubruz\Mainframe\MainframeClient;

class GetUserTimeline extends TwitterJob
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
        if($this->user->twitter_user_timeline_limit < 2){
            return;
        }

        $this->user->twitter_user_timeline_limit = $this->user->twitter_user_timeline_limit -1;
        $this->user->save();

        $this->mainframeClient = new MainframeClient(env('BOT_SECRET'), env('MAINFRAME_API_URL'));
        $this->twitterConnection    = new TwitterOAuth(
            env("TWITTER_API_KEY"),
            env("TWITTER_API_SECRET"),
            $this->user->twitter_oauth_token,
            $this->user->twitter_oauth_token_secret
        );

        $peopleArray = explode(',', $this->subscription->people);

        foreach($peopleArray as $people) {
            $tweets = $this->twitterConnection->get("statuses/user_timeline", [
                "screen_name"   => str_replace('@', '',$people),
                "count"         => 10,
                "tweet_mode"    => "extended",
                "since_id"      => $this->subscription->people_since_id
            ]);

            if ($this->twitterConnection->getLastHttpCode() != 200){
                continue;
            }

            $firstTweet = true;

            foreach($tweets as $tweet) {


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

                if ($firstTweet) {
                    $this->subscription->people_since_id = $tweet->id_str;
                    $this->subscription->save();
                }

                $resp = $this->mainframeClient->sendMessage($this->conversation->mainframe_conversation_id, $tweetUI->getUIPayload(), $this->subscription->mainframe_subscription_id);

                $firstTweet = false;
            }
        }

    }
}
