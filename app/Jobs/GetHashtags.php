<?php

namespace App\Jobs;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Libraries\Tweet;
use Aubruz\Mainframe\MainframeClient;

class GetHashtags extends TwitterJob
{

    /**
     * GetHashtags constructor.
     * @param $conversation
     * @param $subscription
     * @param $user
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
        if($this->user->twitter_search_limit < 2){
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

        $hashtags = str_replace(',', ' OR ', $this->subscription->hashtags);
        $tweets = $this->twitterConnection->get("search/tweets", [
            "q"             => $hashtags . " -filter:retweets AND -filter:replies",
            "result_type"   => "recent",
            "count"         => 10,
            "tweet_mode"    => "extended",
            "since_id"      => $this->subscription->hashtags_since_id
        ]);

        if ($this->twitterConnection->getLastHttpCode() != 200){
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
                str_replace('&amp;', '&', $tweet->full_text),
                $tweet->user->profile_image_url_https,
                $images
            );

            if($firstTweet){
                $this->subscription->hashtags_since_id = $tweet->id_str;
                $this->subscription->save();
            }

            $this->mainframeClient->sendMessage($this->conversation->mainframe_conversation_id, $tweetUI->getUIPayload(), $this->subscription->mainframe_subscription_id);

            $firstTweet = false;
        }

    }
}
