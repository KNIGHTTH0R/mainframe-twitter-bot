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
use Aubruz\Mainframe\UI\Components\MultiSelect;
use Aubruz\Mainframe\UI\Components\RadioButtonSelect;
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

    /**
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        return $this->respondCreated(app()->environment());
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function conversationAdded (Request $request)
    {
        $conversationID = $request->input('conversation_id');
        $mainframeUserID = $request->input('user_id');
        if(!$conversationID || !$mainframeUserID){
            $this->respondBadRequest();
        }
        $this->mainframeClient->sendMessage($conversationID, 'Hi!');

        //Save the new conversation
        $conversation = new Conversation();
        $conversation->mainframe_conversation_id = $conversationID;
        $conversation->save();

        $user = User::where('mainframe_user_id', $mainframeUserID)->first();
        if(!$user){
            $user = new User();
            $user->mainframe_user_id = $mainframeUserID;
            $user->save();
        }

        return $this->respond($this->botResponse->toArray());
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function conversationRemoved(Request $request)
    {
        $conversationID = $request->input('conversation_id');

        if(!$conversationID){
            $this->respondBadRequest();
        }

        $conversation = Conversation::where('mainframe_conversation_id', $conversationID)->first();
        if(!$conversation) {
            $this->botResponse->setSuccess(false);
            return $this->respond($this->botResponse->toArray());
        }

        $conversation->delete();

        return $this->respond($this->botResponse->toArray());
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function deleteSubscription(Request $request)
    {
        $subscriptionID = $request->input('subscription_id');
        if(!$subscriptionID){
            $this->respondBadRequest();
        }

        $subscription = Subscription::where('mainframe_subscription_id', $subscriptionID)->first();
        if(!$subscription){
            $this->botResponse->setSuccess(false);
            return $this->respond($this->botResponse->toArray());
        }

        $subscription->delete();

        return $this->respond($this->botResponse->toArray());
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
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
        }

        return $this->respond($this->botResponse->toArray());
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function disable(Request $request)
    {
        if(!$request->has('user_id')){
            $this->respondBadRequest();
        }
        $mainframeUserID = $request->input('user_id');
        $user = User::where('mainframe_user_id', $mainframeUserID)->first();

        if(!$user) {
            $this->botResponse->setSuccess(false);
            return $this->respond($this->botResponse->toArray());
        }

        $user->delete();

        return $this->respond($this->botResponse->toArray());
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Abraham\TwitterOAuth\TwitterOAuthException
     */
    public function post(Request $request)
    {
        //Mandatory field
        if(!$request->has('context.user_id')) {
            $this->respondBadRequest();
        }

        // Get context
        $mainframeUserID = $request->input('context.user_id');
        $mainframeConversationID = $request->input('context.conversation_id');
        $requestType = $request->input('data.type');
        $subscriptionExists = $request->has('context.subscription_id');
        $subscriptionToken = $request->input('context.subscription_token');
        $mainframeSubscriptionID = $request->input('context.subscription_id');

        // Retrieve user
        $user = User::where('mainframe_user_id', $mainframeUserID)->first();
        if(!$user){
            $user = new User();
            $user->mainframe_user_id = $mainframeUserID;
            $user->save();
        }

        // user not authenticated
        if(!$user->twitter_oauth_token && $request->input('data.payload.type') != 'authentication_success'){
            $url = $this->getTwitterAuthUrl($user);
            $this->botResponse->addData((new AuthenticationData($url))->addPayload(["type"=>"authentication_success"]));
            return $this->respond($this->botResponse->toArray());
        }

        switch($requestType){
            case 'save':

                // Get inputs
                $people = $request->input('data.form.people', '');
                $hashtags = $request->input('data.form.hashtags', '');
                $getMyMention = in_array('mention', $request->input('data.form.user_account', []));
                $getMyTimeline = in_array('timeline', $request->input('data.form.user_account', []));

                //Verification of inputs
                if($people === '' && $hashtags === '' && !$getMyMention && !$getMyTimeline) {
                    $this->botResponse->addMessage("You must choose at least one element of subscription");
                    $this->botResponse->setSuccess(false);
                    return $this->respond($this->botResponse->toArray());
                }

                if(!self::inputCheck($people, '@') || !self::inputCheck($hashtags, '#')){
                    $this->botResponse->addMessage("You must separate your inputs by a comma without space.");
                    $this->botResponse->setSuccess(false);
                    return $this->respond($this->botResponse->toArray());
                }

                // Label creation
                if($people == '' && $hashtags == ''){
                    $label = '@'.$user->twitter_screen_name;
                }else {
                    $label = implode(' ', explode(',', $people)) . ' ' . implode(' ', explode(',', $hashtags));
                }

                // Edit or setup subscription with Mainframe
                if($subscriptionExists){
                    $response = $this->mainframeClient->editSubscription($subscriptionToken, $label);
                }else {
                    $response = $this->mainframeClient->setupSubscription($subscriptionToken, $label);
                }
                $response = json_decode($response->getBody());
                if($response->success){
                    //Create new subscription or edit
                    $conversation = Conversation::where('mainframe_conversation_id', $mainframeConversationID)->first();
                    if(!$subscriptionExists){
                        $subscription = new Subscription();
                        $subscription->conversation_id = $conversation->id;
                        $subscription->user_id = $user->id;
                    }else {
                        $subscription = Subscription::where('mainframe_subscription_id', $mainframeSubscriptionID)->first();
                    }

                    $subscription->label = $label;
                    $subscription->hashtags = $hashtags;
                    $subscription->people = $people;
                    $subscription->mainframe_subscription_id = $response->subscription_id;
                    $subscription->get_my_mention = $getMyMention;
                    $subscription->get_my_timeline = $getMyTimeline;

                    $subscription->save();
                    return $this->respond($this->botResponse->toArray());
                }

                $this->botResponse->addMessage("An error occured, please try again or restart the process.");
                $this->botResponse->setSuccess(false);
                return $this->respond($this->botResponse->toArray());

                break;
            case 'signout':
                $form = (new Form())->addChildren((new RadioButtonSelect('confirm', ''))
                    ->addOptions(['ok' => 'I understand', 'not_ok' => 'Never mind']))
                    ->addData('confirm', 'not_ok');

                $this->botResponse->addData((new ModalData('Are you sure?'))
                    ->setUI((new UIPayload())
                        ->addButton((new Button("OK"))->setPayload(["type"=>"safe_signout"])->setType("form_post"))
                        ->addButton((new Button("Cancel"))->setStyle("close_modal"))
                        ->setRender($form)
                    )
                );
                $this->botResponse->addMessage("If you signout all your subscriptions will be erased!");
                $this->botResponse->setSuccess(false);
                return $this->respond($this->botResponse->toArray());
                break;
            case 'safe_signout':

                if($request->has('data.form.confirm') && $request->input('data.form.confirm') === 'ok') {

                    $user->resetTwitterData();
                    $userSubscriptions = $user->subscriptions;
                    foreach ($userSubscriptions as $subscription) {
                        $this->mainframeClient->deleteSubscription($subscription->conversation->mainframe_conversation_id, $subscription->mainframe_subscription_id);
                    }
                    $url = $this->getTwitterAuthUrl($user);
                    $this->botResponse->addData((new AuthenticationData($url))->addPayload(["type" => "authentication_success"]));
                    return $this->respond($this->botResponse->toArray());
                }
                break;
        }
        $form = (new Form())
            ->addChildren((new TextInput("hashtags", "Hashtags that you want to follow."))->setPrefix("#"))
            ->addChildren((new TextInput("people", "People that you want to follow."))->setPrefix("@"))
            ->addChildren((new MultiSelect('user_account', 'My account'))
                ->addOptions(["timeline" => "Get your timeline"])
                ->addOptions(["mention" => "Get the tweets that mention your name"]));

        if(($requestType === 'edit' || $requestType === 'safe_signout') && $subscriptionExists) {
            $subscription = Subscription::where('mainframe_subscription_id', $mainframeSubscriptionID)->first();
            $form->addData("hashtags", $subscription->hashtags)
                ->addData("people", $subscription->people);
            $userAccount = [];
            if($subscription->get_my_timeline){
                array_push($userAccount, "timeline");
            }
            if($subscription->get_my_mention){
                array_push($userAccount, "mention");
            }
            
            $form->addData("user_account", $userAccount);
        }else {
            $form->addData("hashtags", "#mainframe,#productivity")
                ->addData("people", "@MainframeApp");
        }


        $this->botResponse->addData((new ModalData('Choose you subscription'))
            ->setUI((new UIPayload())
                ->addButton((new Button("Save"))->setPayload(["type"=>"save"])->setType("form_post"))
                ->addButton((new Button("Signout"))->setPayload(["type"=>"signout"])->setStyle("secondary"))
                ->setRender($form)
            )
        );

        return $this->respond($this->botResponse->toArray());
    }

    /**
     * @param User $user
     * @return string
     * @throws \Abraham\TwitterOAuth\TwitterOAuthException
     */
    private function getTwitterAuthUrl(User $user)
    {
        $requestToken = $this->twitterConnection->oauth("oauth/request_token", ["oauth_callback" => env("TWITTER_OAUTH_CALLBACK")]);
        $user->twitter_oauth_request_token = $requestToken["oauth_token"];
        $user->save();
        $url = $this->twitterConnection->url("oauth/authenticate",["oauth_token" => $requestToken["oauth_token"]]);
        return $url;
    }

    /**
     * @param $input
     * @param $prefix
     * @return bool
     */
    private static function inputCheck($input, $prefix){
        if($input !== '') {
            $input = explode(',', $input);
            foreach ($input as $value) {
                if (substr($value, 0, 1) !== $prefix) {
                    return false;
                }
            }
        }
        return true;
    }
}
