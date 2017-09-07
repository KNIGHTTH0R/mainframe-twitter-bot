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
        if($request->has("denied")){
            return redirect("https://staging.mainframe.com/bots/auth/?state=cancel&name=Twitter%20Bot&logo_url=http://www.clipartbest.com/cliparts/ecM/kgb/ecMkgbB5i.png");
        }

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

        if(!$user){
            return redirect("https://staging.mainframe.com/bots/auth/?state=error&name=Twitter%20Bot&logo_url=http://www.clipartbest.com/cliparts/ecM/kgb/ecMkgbB5i.png");
        }

        $user->twitter_oauth_token = $access_token["oauth_token"];
        $user->twitter_oauth_token_secret = $access_token["oauth_token_secret"];
        $user->twitter_user_id = $access_token["user_id"];
        $user->twitter_screen_name = $access_token["screen_name"];
        $user->save();

        return redirect("https://staging.mainframe.com/bots/auth/?state=success&name=Twitter%20Bot&logo_url=http://www.clipartbest.com/cliparts/ecM/kgb/ecMkgbB5i.png");
    }

    public function webhook()
    {

    }

    public function crcCheck(Request $request)
    {
        if(!$request->has('crc_token')){
            $this->respondBadRequest();
        }

        $crcToken = $request->has('crc_token');
        $hashDigest = hash_hmac('sha256', $crcToken, env("TWITTER_API_SECRET"));

        return $this->respond([
            "response_token" => 'sha256=' . base64_encode($hashDigest)
        ]);
    }
}
