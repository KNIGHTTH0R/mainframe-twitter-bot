<?php

namespace App\Jobs;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Libraries\Tweet;
use Aubruz\Mainframe\MainframeClient;

class GetList extends TwitterJob
{

    /**
     * GetList constructor.
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
        $this->mainframeClient      = new MainframeClient(env('BOT_SECRET'), env('MAINFRAME_API_URL'));
        $this->twitterConnection    = new TwitterOAuth(
                                            env("TWITTER_API_KEY"),
                                            env("TWITTER_API_SECRET"),
                                            $this->user->twitter_oauth_token,
                                            $this->user->twitter_oauth_token_secret
                                        );

        foreach($this->user->twitterlists as $list) {

            $subscriptions = $list->subscriptions;

            if(!$subscriptions){
                continue;
            }

            if($this->user->twitter_show_list_limit < 2){
                $this->delete();
                return;
            }

            $this->user->twitter_show_list_limit = $this->user->twitter_show_list_limit -1;
            $this->user->save();

            $tweets = $this->twitterConnection->get("lists/show", [
                "list_id" => $list->twitter_id,
                "tweet_mode" => "extended",
                "since_id" => $list->twitter_list_since_id
            ]);

            if ($this->twitterConnection->getLastHttpCode() != 200) {
                $this->delete();
                return;
            }

            $firstTweet = true;
            foreach ($tweets->statuses as $tweet) {

                $images = [];
                if (property_exists($tweet, "entities") && property_exists($tweet->entities, "media")) {
                    foreach ($tweet->entities->media as $media) {
                        array_push($images, [
                            "url" => $media->media_url_https,
                            "width" => $media->sizes->small->w,
                            "height" => $media->sizes->small->h,
                        ]);
                    }
                }
                $tweetUI = new Tweet(
                    $tweet->id_str,
                    'url',
                    $tweet->created_at,
                    $tweet->user->name,
                    $tweet->user->screen_name,
                    htmlspecialchars_decode($tweet->full_text),
                    $tweet->user->profile_image_url_https,
                    $images
                );

                if ($firstTweet) {
                    $this->subscription->hashtags_since_id = $tweet->id_str;
                    $this->subscription->save();
                }

                foreach ($subscriptions as $subscription) {
                    $this->mainframeClient->sendMessage($subscription->conversation->mainframe_conversation_id, $tweetUI->getUIPayload(), $subscription->mainframe_subscription_id);
                }

                $firstTweet = false;

            }
        }

    }
}
