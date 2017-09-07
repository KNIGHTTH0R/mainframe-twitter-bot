<?php

namespace App\Libraries;

use Aubruz\Mainframe\UI\Components\Image;
use \Aubruz\Mainframe\UI\Components\Message;
use \Aubruz\Mainframe\UI\Components\Author;
use \Aubruz\Mainframe\UI\Components\Text;
use Aubruz\Mainframe\Responses\UIPayload;

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
     * Tweet constructor.
     * @param $userName
     * @param $screenName
     * @param $text
     * @param $avatar
     * @param $images
     */
    public function __construct($userName, $screenName, $text, $avatar, $images = [])
    {
        $message = new Message();
        $message->addChildren((new Author($userName, '@'.$screenName ))->addAvatarUrl($avatar)->isCircle());
        $message->addChildren((new Text())->addChildren($text));

        if($images){
            foreach($images as $image){
                $message->addChildren((new Image($image["url"], $image["height"], $image["width"]))->allowOpenFullImage());
            }
        }

        $this->uiPayload = new UIPayload();
        $this->uiPayload->setRender($message);
    }

    /**
     * @return array
     */
    public function getUIPayload()
    {
        return $this->uiPayload;
    }
}