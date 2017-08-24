<?php

namespace App\Http\Controllers;

use Aubruz\Mainframe\MainframeClient;
use Aubruz\Mainframe\Response\AuthenticationData;
use Aubruz\Mainframe\Response\BotResponse;
use Aubruz\Mainframe\Response\ModalData;
use Aubruz\Mainframe\Response\UIPayload;
use Aubruz\Mainframe\UI\Components\TextInput;
use Aubruz\Mainframe\UI\Components\Form;
use Aubruz\Mainframe\UI\Button;
use Illuminate\Http\Request;
use Abraham\TwitterOAuth\TwitterOAuth;


class BotController extends ApiController
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

    public function index()
    {
        return $this->respondCreated(app()->environment());
    }

    public function conversationAdded (Request $request)
    {
        $conversationId = $request->input('conversation_id');
        if(is_null($conversationId)){
            $this->respondBadRequest();
        }
        $this->mainframeClient->sendMessage($conversationId, 'Hello World!!');
        return $this->respond(["success" => true]);
    }

    public function conversationRemoved()
    {
        // Delete all subscription
        return $this->respond(["success" => true]);
    }

    public function deleteSubscription()
    {
        //Delete subscription in database
        return $this->respond(['success'   => true]);
    }

    public function post(Request $request)
    {
        if(!$request->has('context.user_id') || !$request->has('context.conversation_id') || !$request->has('context.subscription_token')) {
            $this->respondBadRequest();
        }

        $requestType = $request->input('data.type');

        $botResponse = new BotResponse();

        switch($requestType){
            case 'save':
                //Verification of inputs
                //Save subscription in database
                $this->mainframeClient->setupSubscription($request->input('context.subscription_token'), "Subscription label");
                break;
            case 'signin':
                $requestToken = $this->twitterConnection->oauth("oauth/request_token", ["oauth_callback" => "http://44d858b4.ngrok.io/oauth/request_token"]);
                $url = $this->twitterConnection->url("oauth/authenticate", $requestToken);
                $botResponse->addData(new AuthenticationData($url));
                return $this->respond($botResponse->toArray());
                break;
        }


        $botResponse->addMessage('Signin with your twitter account to get direct messages and/or tweets that mention your name.');
        $botResponse->addData((new ModalData('Choose you subscription'))
            ->setUI((new UIPayload())
                ->addButton((new Button("Save"))->setPayload(["type"=>"save"])->setType("form_post"))
                ->addButton((new Button("Signin with Twitter"))->setPayload(["type"=>"signin"])->setStyle("secondary"))
                ->setRender((new Form())
                    ->addChildren((new TextInput("hashtags", "Enter the hashtags that you want to follow."))->setPrefix("#"))
                    ->addChildren((new TextInput("people", "Enter the people that you want to follow."))->setPrefix("@"))
                    ->addData("hashtags", "#mainframe,#productivity")
                    ->addData("people", "@MainframeApp")
                )
            )
        );

        return $this->respond($botResponse->toArray());
    }
}
