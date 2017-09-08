<?php

namespace App\Libraries;

use Aubruz\Mainframe\UI\Components\Image;
use \Aubruz\Mainframe\UI\Components\Message;
use \Aubruz\Mainframe\UI\Components\Author;
use Aubruz\Mainframe\UI\Components\MessageButton;
use \Aubruz\Mainframe\UI\Components\Text;
use Aubruz\Mainframe\Responses\UIPayload;
use Aubruz\Mainframe\UI\Components\TextLink;

/**
 * Class Tweet
 */
class Tweet
{
    /**
     * @var UIPayload
     */
    private $uiPayload;

    /**
     * @var
     */
    private $match;

    /**
     * Tweet constructor.
     * @param $userName
     * @param $screenName
     * @param $text
     * @param $avatar
     * @param $images
     */
    public function __construct($tweetID, $tweetUrl, $userName, $screenName, $text, $avatar, $images = [])
    {
        $message = new Message();
        $message->addChildren((new Author($userName, '@'.$screenName ))->addAvatarUrl($avatar)->isCircle());
        $message->addChildren((new Text())->addChildren($text));

        if($images){
            foreach($images as $image){
                $message->addChildren((new Image($image["url"], $image["height"], $image["width"]))->allowOpenFullImage());
            }
        }

        $likeButton =(new MessageButton("Like"))->setType("post_payload")->setPayload([
            "type"      => "like",
            "tweet_id"  => $tweetID
        ]);

        $replyButton =(new MessageButton("Reply"))->setType("open_modal")->setPayload([
            "type"          => "get_reply_form",
            "tweet_id"      => $tweetID,
            "tweet_author"  => '@'.$screenName
        ]);

        $retweetButton =(new MessageButton("Retweet"))->setType("open_modal")->setPayload([
            "type"      => "get_retweet_form",
            "tweet_id"  => $tweetID,
            "tweet_url" => $tweetUrl
        ]);

        $this->uiPayload = new UIPayload();
        $this->uiPayload->setRender($message)->addButton($replyButton);
        $this->uiPayload->setRender($message)->addButton($retweetButton);
        $this->uiPayload->setRender($message)->addButton($likeButton);
    }

    /**
     * @return array
     */
    public function getUIPayload()
    {
        return $this->uiPayload;
    }

    /**
     * @param $text
     * @return Text
     * @throws \Aubruz\Mainframe\Exceptions\UIException
     */
    private function formatText($text)
    {
        $textObject = new Text();

        $textArray = explode(' ',$text);
        foreach($textArray as $text){
            if($this->isHashtag($text)){
                //dd($this->match);
                $url = 'https://twitter.com/hashtag/'.$this->match[1];
                $textObject->addChildren((new TextLink($url))->addChildren('#'.$this->match[1]));
                if(count($this->match) > 2) {
                    $textObject->addChildren($this->match[2]);
                }
            }else if($this->isScreenName($text)){
                $url = 'https://twitter.com/'.$this->match[1];
                $textObject->addChildren((new TextLink($url))->addChildren('@'.$this->match[1]));
                if(count($this->match) > 2) {
                    $textObject->addChildren($this->match[2]);
                }
            }else if($this->isUrl($text)){
                $textObject->addChildren((new TextLink($this->match[1]))->addChildren($this->match[1]));
                if(count($this->match) > 2) {
                    $textObject->addChildren($this->match[2]);
                }
            }else{
                $textObject->addChildren($text);
            }
            $textObject->addChildren(' ');
            $this->match = null;
        }
        return $textObject;
    }

    /**
     * @param $word
     * @return int
     */
    private function isHashtag($word)
    {
        return preg_match('/#([a-zA-Z0-9]+)([\W\D]?.*)$/',$word, $this->match);
    }

    /**
     * @param $word
     * @return int
     */
    private function isScreenName($word)
    {
        return preg_match('/@([a-zA-Z0-9]+)([\W\D]?.*)$/',$word, $this->match);
    }

    /**
     * @param $word
     * @return int
     */
    private function isUrl($word)
    {
        return preg_match('/(https?:\/\/[-A-Z0-9+&@#\/%?=~_|!:,.;]*[A-Z0-9+&@#\/%=~_|])([:,.?!"\']?.*)/i', $word, $this->match);
    }
}