<?php

namespace App\Jobs;

use Abraham\TwitterOAuth\TwitterOAuth;
use Aubruz\Mainframe\MainframeClient;
use Aubruz\Mainframe\Response\BotResponse;
use Aubruz\Mainframe\Response\EmbedData;
use Aubruz\Mainframe\Response\UIPayload;
use Aubruz\Mainframe\UI\Components\Author;
use Aubruz\Mainframe\UI\Components\Message;

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
            "q"             => urlencode("#geneva"),
            "result_type"   => "recent",
            "count"         => 1
        ]);

        foreach($response->statuses as $tweet){
            //dd($tweet);

            $message = new Message("New tweet");
            $message->addChildren(new Author($tweet->user->name, $tweet->user->screen_name ));


            $botResponse = new BotResponse();
            $botResponse->addData((new EmbedData())->setUI(
                (new UIPayload())->setRender($message))
            );

            $this->mainframeClient->sendMessage($this->conversation->mainframe_conversation_id, $botResponse->toArray());
        }


        // Send in conversation
    }
}
