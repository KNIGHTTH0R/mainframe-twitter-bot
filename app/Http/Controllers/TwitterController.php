<?php

namespace App\Http\Controllers;

use Aubruz\Mainframe\MainframeClient;
use Abraham\TwitterOAuth\TwitterOAuth;
use Symfony\Component\HttpFoundation\Request;


class TwitterController extends ApiController
{
    /**
     * @var MainframeClient
     */
    private $mainframeClient;


    /**
     * BotController constructor.
     */
    public function __construct()
    {
        $this->mainframeClient = new MainframeClient(env('BOT_SECRET'), env('MAINFRAME_API_URL'));
    }


    public function requestToken(Request $request)
    {

    }

}
