<?php

namespace App\Http\Controllers;

use Aubruz\Mainframe\MainframeClient;
use Abraham\TwitterOAuth\TwitterOAuth;
use Illuminate\Http\Request;


class TwitterController extends ApiController
{
    /**
     * @var MainframeClient
     */
    private $mainframeClient;

    /**
     * @var TwitterOAuth
     */
    private $twitterConnection;

    /**
     * BotController constructor.
     */
    public function __construct()
    {
        $this->mainframeClient = new MainframeClient(env('BOT_SECRET'), env('MAINFRAME_API_URL'));
        $this->twitterConnection = new TwitterOAuth(getenv("TWITTER_API_KEY"), getenv("TWITTER_API_SECRET"));
    }


    public function requestToken(Request $request)
    {
        if(!$request->has('oauth_verifier') || !$request->has('oauth_token')) {
            return $this->respondBadRequest();
        }
        $oauthVerifier = $request->input('oauth_verifier');
        $oauthToken = $request->input('oauth_token');

        $access_token = $this->twitterConnection->oauth("oauth/access_token", [
            "oauth_verifier"    => $oauthVerifier,
            "oauth_token"       => $oauthToken,
        ]);
        /*
         * {"oauth_token":"2922900753-MvhSH8q391FvQX3WGoAFNodfjqvGfH8fg8GZj80",
         * "oauth_token_secret":"I5BYBa2sLWZAN5QtpOr3koTi26NWvpkU2RWBfmfjCxAzI",
         * "user_id":"2922900753",
         * "screen_name":"aubruz",
         * "x_auth_expires":"0"}
        */
        return $access_token;

    }

}
