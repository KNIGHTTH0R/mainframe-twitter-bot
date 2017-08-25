<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Subscription;
use App\Models\User;
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


/**
 * Class BotController
 * @package App\Http\Controllers
 */
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
     * @var BotResponse
     */
    private $botResponse;


    /**
     * BotController constructor.
     */
    public function __construct()
    {
        $this->mainframeClient      = new MainframeClient(env('BOT_SECRET'), env('MAINFRAME_API_URL'));
        $this->twitterConnection    = new TwitterOAuth(env("TWITTER_API_KEY"), env("TWITTER_API_SECRET"));
        $this->botResponse          = new BotResponse();
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

        //Save the new conversation
        $conversation = new Conversation();
        $conversation->conversation_id = $conversationId;
        $conversation->save();

        //TODO
        // Find user
        // Add him if he/she does not exists yet

        return $this->respond($this->botResponse->toArray());
    }

    public function conversationRemoved()
    {
        //TODO
        // Delete all subscription linked to the conversation
        return $this->respond($this->botResponse->toArray());
    }

    public function deleteSubscription()
    {
        //TODO
        //Delete the subscription in database
        return $this->respond($this->botResponse->toArray());
    }

    public function enable(Request $request)
    {
        if(!$request->has('user_id')){
            $this->respondBadRequest();
        }
        $mainframeUserID = $request->input('user_id');
        $user = User::where('mainframe_user_id', $mainframeUserID)->first();
        if(!$user){
            // This is a new user
            $user = new User();
            $user->mainframe_user_id = $mainframeUserID;
            $user->save();
        }// else, nothing to do

        return $this->respond($this->botResponse->toArray());
    }

    public function disable()
    {
        //TODO
        //Delete the user and subscriptions attached
        return $this->respond($this->botResponse->toArray());
    }

    public function post(Request $request)
    {
        if(!$request->has('context.user_id') || !$request->has('context.conversation_id') || !$request->has('context.subscription_token')) {
            $this->respondBadRequest();
        }

        $requestType = $request->input('data.type');
        $subscriptionExists = $request->has('context.subscription_id');


        switch($requestType){
            case 'save':
                //Verification of inputs
                if($request->has('data.form.people')){

                    $label = $request->input('data.form.people') . ' ' . $request->input('data.form.hashtags');
                    if($subscriptionExists){
                        $response = $this->mainframeClient->editSubscription($request->input('context.subscription_token'), $label);
                    }else {
                        $response = $this->mainframeClient->setupSubscription($request->input('context.subscription_token'), $label);
                    }
                    $response = json_decode($response->getBody());
                    if($response->success){
                        //Save subscription in database
                        //TODO
                    }
                    return $response;
                }

                break;
            case 'signin':
                $requestToken = $this->twitterConnection->oauth("oauth/request_token", ["oauth_callback" => "http://44d858b4.ngrok.io/oauth/request_token"]);
                $url = $this->twitterConnection->url("oauth/authenticate", $requestToken);
                $this->botResponse->addData(new AuthenticationData($url));
                return $this->respond($this->botResponse->toArray());
                break;
            case null:
                if($subscriptionExists){
                    // Edit subscription
                    //TODO

                }else{
                    // Return Initial screen
                    //TODO
                }
                break;
        }


        $this->botResponse->addMessage('Signin with your twitter account to get more options.');
        $this->botResponse->addData((new ModalData('Choose you subscription'))
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

        return $this->respond($this->botResponse->toArray());
    }
}
