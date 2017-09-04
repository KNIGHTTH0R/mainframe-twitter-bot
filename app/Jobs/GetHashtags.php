<?php

namespace App\Jobs;

use Abraham\TwitterOAuth\TwitterOAuth;
use App\Libraries\Tweet;
use Aubruz\Mainframe\MainframeClient;
use Aubruz\Mainframe\Response\BotResponse;
use Aubruz\Mainframe\Response\EmbedData;
use Aubruz\Mainframe\Response\UIPayload;
use Aubruz\Mainframe\UI\Components\Author;
use Aubruz\Mainframe\UI\Components\Message;
use Aubruz\Mainframe\UI\Components\Text;

class GetHashtags extends Job
{
    /**
     * @var string
     */
    private $hashtags;


    /**
     * GetHashtags constructor.
     * @param $conversation
     * @param $subscription
     * @param $user
     * @param $hashtags
     */
    public function __construct($conversation, $subscription, $user, $hashtags)
    {
        parent::__construct($conversation, $subscription, $user);
        $this->hashtags = $hashtags;
        //
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


        $response = $this->twitterConnection->get("search/tweets", [
            "q"             => "#TabloidCover -filter:retweets AND -filter:replies",
            "result_type"   => "recent",
            "count"         => 2
        ]);

        foreach($response->statuses as $tweet){

            //dd($tweet);
            $image = null;
            if(property_exists($tweet, "media")){
                $image = $tweet->media[0]->media_url_https;
            }
            $tweetUI = new Tweet(
                $tweet->user->name,
                $tweet->user->screen_name,
                $tweet->text,
                $tweet->user->profile_image_url_https,
                $image
            );
           // $tweet->id_str;

            $resp = $this->mainframeClient->sendMessage($this->conversation->mainframe_conversation_id, $tweetUI->getUIPayload(), $this->subscription->mainframe_subscription_id);
            //d($response);
        }

    }
}
