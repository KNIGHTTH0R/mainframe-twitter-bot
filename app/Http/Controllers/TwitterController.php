<?php

namespace App\Http\Controllers;

use App\Models\User;
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
        $this->twitterConnection = new TwitterOAuth(env("TWITTER_API_KEY"), env("TWITTER_API_SECRET"));
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

        $user = User::where("twitter_oauth_request_token", $oauthToken)->first();
        //TODO handle case where user is not found

        $user->twitter_oauth_token = $access_token["oauth_token"];
        $user->twitter_oauth_token_secret = $access_token["oauth_token_secret"];
        $user->twitter_user_id = $access_token["user_id"];
        $user->twitter_screen_name = $access_token["screen_name"];
        $user->save();
        return $access_token;

    }

    public function webhook()
    {

    }

}
