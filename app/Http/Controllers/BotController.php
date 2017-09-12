<?php

namespace App\Http\Controllers;

use App\Models\Conversation;
use App\Models\Subscription;
use App\Models\TwitterList;
use App\Models\User;
use Aubruz\Mainframe\MainframeClient;
use Aubruz\Mainframe\Responses\AuthenticationData;
use Aubruz\Mainframe\Responses\BotResponse;
use Aubruz\Mainframe\Responses\ModalData;
use Aubruz\Mainframe\Responses\UIPayload;
use Aubruz\Mainframe\UI\Components\CheckboxGroup;
use Aubruz\Mainframe\UI\Components\CheckboxItem;
use Aubruz\Mainframe\UI\Components\Dropdown;
use Aubruz\Mainframe\UI\Components\ModalButton;
use Aubruz\Mainframe\UI\Components\MultiLineInput;
use Aubruz\Mainframe\UI\Components\MultiSelect;
use Aubruz\Mainframe\UI\Components\RadioButtonSelect;
use Aubruz\Mainframe\UI\Components\TextInput;
use Aubruz\Mainframe\UI\Components\Form;
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

        $user = (new User)->where('mainframe_user_id', $mainframeUserID)->first();
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
        if(!$request->has('conversation_id')){
            $this->respondBadRequest();
        }

        $conversation = (new Conversation)->where('mainframe_conversation_id', $request->input('conversation_id'))->first();
        if(!$conversation) {
            return $this->respondWithError();
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
        if(!$request->has('subscription_id')){
            $this->respondBadRequest();
        }

        $subscription = (new Subscription)->where('mainframe_subscription_id', $request->input('subscription_id'))->first();
        if(!$subscription){
            return $this->respondWithError();
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

        $user = (new User)->where('mainframe_user_id', $request->input('user_id'))->first();
        if(!$user){
            // This is a new user
            $user = new User();
            $user->mainframe_user_id = $request->input('user_id');
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

        $user = (new User)->where('mainframe_user_id', $request->input('user_id'))->first();
        if(!$user) {
            return $this->respondWithError();
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
        $requestType = $request->input('data.type');
        $subscriptionExists = $request->has('context.subscription_id');
        $mainframeSubscriptionID = $request->input('context.subscription_id');

        // Retrieve user
        $user = (new User)->where('mainframe_user_id', $mainframeUserID)->first();
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

        // To make twitter api calls in the behalf of the user
        $this->twitterConnection->setOauthToken($user->twitter_oauth_token, $user->twitter_oauth_token_secret);

        switch($requestType){
            case 'authentication_success':
                // Get twitter lists of user
                $lists = $this->twitterConnection->get("lists/list");

                if ($this->twitterConnection->getLastHttpCode() != 200) {
                    return $this->respondWithError("A problem occured. Please retry or signout and signin again.");
                }

                foreach ($lists as $list) {
                    $twitterList = new TwitterList();
                    $twitterList->twitter_id = $list->id_str;
                    $twitterList->twitter_name = $list->name;
                    $twitterList->twitter_slug = $list->slug;
                    $twitterList->user_id = $user->id;
                    $twitterList->save();
                }

                break;
            case 'update_user_lists':
                if($user->twitter_get_lists_limit > 1) {
                    $lists = $this->twitterConnection->get("lists/list");
                    if ($this->twitterConnection->getLastHttpCode() != 200) {
                        return $this->respondWithError("A problem occured. Please retry or signout and signin again.");
                    }
                    $listsInDB = (new Twitterlist)->pluck('twitter_id')->all();

                    //Synchronize lists
                    foreach ($lists as $list) {
                        if(!in_array($list->id_str , $listsInDB)){
                            //Add the missing lists
                            $twitterList = new TwitterList();
                            $twitterList->twitter_id = $list->id_str;
                            $twitterList->twitter_name = $list->name;
                            $twitterList->twitter_slug = $list->slug;
                            $twitterList->user_id = $user->id;
                            $twitterList->save();
                        }else{
                            $key = array_search($list->id_str, $listsInDB);
                            if($key !== false) {
                                unset($listsInDB[$key]);
                            }
                        }
                    }
                    // Delete the lists that doesn't exist anymore
                    (new TwitterList)->whereIn('twitter_id', $listsInDB)->delete();

                    $this->botResponse->addMessage("Lists updated successfully!");
                }
                break;
            case 'get_new_tweet_form':
                return $this->getNewTweetForm();

            case 'send_new_tweet':
                return $this->sendNewTweet($request);

            case 'save':
                return $this->save($request, $user);

            case 'get_signout_form':
                return $this->getSignoutForm();

            case 'signout':
                return $this->signout($request, $user);

            case 'get_reply_form':
                return $this->getReplyForm($request);

            case 'send_reply':
                return $this->sendReply($request);

            case 'get_retweet_form':
                return $this->getRetweetForm($request);

            case 'send_retweet':
                return $this->sendRetweet($request);

            case 'like':
                return $this->like($request);
        }

        $twitterListDropdown = new Dropdown('lists', 'My lists');
        if(count($user->twitterLists) > 0) {
            $twitterListDropdown->setPlaceholder("Select a list");
            foreach ($user->twitterLists as $list) {
                $twitterListDropdown->addOptions([$list->id => $list->twitter_name]);
            }
            $twitterListDropdown->addOptions(["-1" => "None"]);
        }else{
            $twitterListDropdown->disable();
        }

        $form = (new Form())
            ->addChildren((new TextInput("search", "Search: #hashtag, word, @username"))->setPrefix("Search"))
            ->addChildren((new CheckboxGroup(" "))
                ->addChildren(new CheckboxItem("get_search_retweets", "Include retweets"))
                ->addChildren(new CheckboxItem("get_search_replies", "Include replies"))
            )
            ->addChildren((new TextInput("people", "People that you want to follow."))->setPrefix("@"))
            ->addChildren($twitterListDropdown)
            ->addChildren((new MultiSelect('user_account', 'My account'))
                ->addOptions(["timeline" => "Get your timeline"])
                ->addOptions(["mention" => "Get the tweets that mention your name"]));

        if(($requestType === 'edit' || $requestType === 'signout') && $subscriptionExists) {
            $subscription = (new Subscription)->where('mainframe_subscription_id', $mainframeSubscriptionID)->first();
            if(!$subscription){
                return $this->respondWithError("An error occured, please restart the process from the beginning.");
            }
            // Fill form with database data
            if($subscription->search != '') {
                $form->addData("search", $subscription->search);
            }
            if($subscription->people != '') {
                $form->addData("people", $subscription->people);
            }
            $userAccount = [];
            if($subscription->get_my_timeline){
                array_push($userAccount, "timeline");
            }
            if($subscription->get_my_mention){
                array_push($userAccount, "mention");
            }
            if($subscription->get_search_retweets != '') {
                $form->addData("get_search_retweets", $subscription->get_search_retweets);
            }
            if($subscription->get_search_replies != '') {
                $form->addData("get_search_replies", $subscription->get_search_replies);
            }
            if($subscription->twitter_list_id != null) {
                $form->addData("lists", $subscription->twitter_list_id);
            }

            if(count($userAccount) > 0) {
               $form->addData("user_account", $userAccount);
            }
        }else {
            $form->addData("search", "#mainframe,landscape")
                ->addData("people", "@MainframeApp");
        }


        $this->botResponse->addData((new ModalData('Choose you subscription'))
            ->setUI((new UIPayload())
                ->addButton((new ModalButton("Save"))->setPayload(["type"=>"save"])->setType("form_post")->setStyle("primary"))
                ->addButton((new ModalButton("Signout"))->setPayload(["type"=>"get_signout_form"])->setStyle("secondary")->setType("post_payload"))
                ->addButton((new ModalButton("Reload lists"))->setPayload(["type"=>"update_user_lists"])->setStyle("secondary")->setType("post_payload"))
                ->setRender($form)
            )
        );

        return $this->respond($this->botResponse->toArray());
    }

    /**
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    private function save(Request $request, User $user)
    {
        // Get inputs
        $people = $request->input('data.form.people', '');
        $search = $request->input('data.form.search', '');
        $getMyMention = in_array('mention', $request->input('data.form.user_account', []));
        $getMyTimeline = in_array('timeline', $request->input('data.form.user_account', []));
        $getSearchReplies = $request->input('data.form.get_search_replies', false);
        $getSearchRetweets = $request->input('data.form.get_search_retweets', false);
        $listID = $request->input('data.form.lists', false);
        $subscriptionToken = $request->input('context.subscription_token');
        $mainframeConversationID = $request->input('context.conversation_id');
        $mainframeSubscriptionID = $request->input('context.subscription_id');

        //Verification of inputs
        if($people === '' && $search === '' && !$getMyMention && !$getMyTimeline && (!$listID || $listID == "-1")) {
            return $this->respondWithError("You must choose at least one element of subscription");
        }

        if(!self::inputCheck($people, '@')){
            return $this->respondWithError("Don't forget the @ in the people input!");
        }

        // Label creation
        if($people == '' && $search == ''){
            $label = '@'.$user->twitter_screen_name;
        }else {
            $label = implode(' ', explode(',', $people)) . ' ' . implode(' ', explode(',', $search)). ' ';
        }

        // Edit or setup subscription with Mainframe
        if($subscriptionToken){
            $response = $this->mainframeClient->editSubscription($subscriptionToken, $label);
        }else {
            $response = $this->mainframeClient->setupSubscription($subscriptionToken, $label);
        }

        $response = json_decode($response->getBody());
        if($response->success){
            //Create new subscription or edit
            $conversation = (new Conversation)->where('mainframe_conversation_id', $mainframeConversationID)->first();
            if(!$subscriptionToken){
                $subscription = new Subscription();
                $subscription->conversation_id = $conversation->id;
                $subscription->user_id = $user->id;
            }else {
                $subscription = (new Subscription)->where('mainframe_subscription_id', $mainframeSubscriptionID)->first();
            }

            $subscription->label = $label;
            $subscription->search = $search;
            $subscription->people = $people;
            $subscription->mainframe_subscription_id = $response->subscription_id;
            $subscription->get_my_mention = $getMyMention;
            $subscription->get_my_timeline = $getMyTimeline;
            $subscription->get_search_retweets = $getSearchRetweets;
            $subscription->get_search_replies = $getSearchReplies;
            $subscription->twitter_list_id = null;
            if($listID && $listID != '-1') {
                $subscription->twitter_list_id = $listID;
            }

            $subscription->save();
            return $this->respond($this->botResponse->toArray());
        }

        return $this->respondWithError("An error occured, please try again or restart the process.");
    }

    /**
     * @return \Illuminate\Http\JsonResponse
     * @throws \Aubruz\Mainframe\Exceptions\UIException
     */
    private function getSignoutForm()
    {
        $form = (new Form())->addChildren((new RadioButtonSelect('confirm', ''))
            ->addOptions(['ok' => 'I understand', 'not_ok' => 'Never mind']))
            ->addData('confirm', 'not_ok');

        $this->botResponse->addData((new ModalData('Are you sure?'))
            ->setUI((new UIPayload())
                ->addButton((new ModalButton("OK"))->setPayload(["type"=>"signout_with_confirmation"])->setType("form_post")->setStyle("primary"))
                ->addButton((new ModalButton("Cancel"))->setStyle("close_modal")->setType("post_payload"))
                ->setRender($form)
            )
        );
        return $this->respondWithError("If you signout all your subscriptions will be erased!");
    }

    /**
     * @param Request $request
     * @param User $user
     * @return \Illuminate\Http\JsonResponse
     */
    private function signout(Request $request, User $user)
    {
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

        return $this->respondWithError();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Aubruz\Mainframe\Exceptions\UIException
     */
    private function getReplyForm(Request $request)
    {
        if($request->has('data.tweet_id') && $request->has('data.tweet_author')) {
            $form = new Form();
            $form->addChildren((new MultiLineInput('reply_text','Reply')));
            $this->botResponse->addData((new ModalData('Reply to '.$request->input('data.tweet_author')))
                ->setUI((new UIPayload())
                    ->addButton((new ModalButton("Send reply"))->setPayload(["type"=>"send_reply"])->setType("form_post")->setStyle("primary"))
                    ->addButton((new ModalButton("Cancel"))->setStyle("secondary")->setType("close_modal"))
                    ->setRender($form)
                )
            );
            return $this->respond($this->botResponse->toArray());
        }

        return $this->respondWithError();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function sendReply(Request $request)
    {
        if ($request->has('data.tweet_id') && $request->has('data.tweet_author') && $request->has('data.form.reply_text')) {
            $this->twitterConnection->post("statuses/update", [
                "in_reply_to_status_id" => $request->input('data.tweet_id'),
                "status" => $request->input('data.form.reply_text')
            ]);
            return $this->respond($this->botResponse->toArray());
        }

        return $this->respondWithError();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * @throws \Aubruz\Mainframe\Exceptions\UIException
     */
    private function getRetweetForm(Request $request)
    {
        if($request->has('data.tweet_id')) {
            $form = new Form();
            $form->addChildren((new MultiLineInput('retweet_text','Add a comment')));
            $this->botResponse->addData((new ModalData('Retweet'))
                ->setUI((new UIPayload())
                    ->addButton((new ModalButton("Retweet"))->setPayload(["type"=>"send_retweet"])->setType("form_post")->setStyle("primary"))
                    ->addButton((new ModalButton("Cancel"))->setStyle("secondary")->setType("close_modal"))
                    ->setRender($form)
                )
            );
            return $this->respond($this->botResponse->toArray());
        }

        return $this->respondWithError();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function sendRetweet(Request $request)
    {
        if($request->has('data.tweet_id') && $request->has('data.tweet_url')) {

            if($request->has('data.form.retweet_text') && $request->input('data.form.retweet_text') != ''){
                if(strlen($request->input('data.form.retweet_text')) > 117){
                    return $this->respondWithError("Due to a restriction from tweeter, a tweet cannot exceed 117 characters in a retweet comment!");
                }else {
                    //Retweet with quote
                    $text = $request->input('data.form.retweet_text') . ' ' . $request->input('data.tweet_url');
                    $this->twitterConnection->post("statuses/update", [
                        "status" => $text
                    ]);
                }
            }else{
                //Simple retweet
                $this->twitterConnection->post("statuses/retweet", [
                    "id" => $request->input('data.tweet_id')
                ]);

            }
            if ($this->twitterConnection->getLastHttpCode() != 200){
                return $this->respondWithError("An error occured, please try again later.");
            }

            return $this->respond($this->botResponse->toArray());
        }
        return $this->respondWithError();
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    private function like(Request $request)
    {
        if($request->has('data.tweet_id')) {
            $this->twitterConnection->post("favorites/create", ["id" => $request->input('data.tweet_id')]);

            return $this->respond($this->botResponse->toArray());
        }
        return $this->respondWithError();
    }

    /**
     * @param Request $request
     * @return array|\Illuminate\Http\JsonResponse
     */
    private function sendNewTweet(Request $request)
    {
        if($request->has('data.form.tweet_text') && $request->input('data.form.tweet_text') != ''){
            if(strlen($request->input('data.form.tweet_text')) > 140){
                return $this->respondWithError("The text cannot exceed 140 characters!");
            }else {
                $this->twitterConnection->post("statuses/update", [
                    "status" => $request->input('data.form.tweet_text')
                ]);
                if ($this->twitterConnection->getLastHttpCode() != 200){
                    return $this->respondWithError("An error occured, please try again later.");
                }
            }
            return $this->botResponse->toArray();
        }

        return $this->respondWithError("The text cannot be empty!");
    }

    /**
     * @return array
     * @throws \Aubruz\Mainframe\Exceptions\UIException
     */
    private function getNewTweetForm()
    {
        $form = new Form();
        $form->addChildren((new MultiLineInput('tweet_text','Tweet message (max: 140 chars.)')));
        $this->botResponse->addData((new ModalData('Send new tweet'))
            ->setUI((new UIPayload())
                ->addButton((new ModalButton("Send tweet"))->setPayload(["type"=>"send_new_tweet"])->setType("form_post")->setStyle("primary"))
                ->addButton((new ModalButton("Cancel"))->setStyle("secondary")->setType("close_modal"))
                ->setRender($form)
            )
        );
        return $this->botResponse->toArray();
    }

    /**
     * @param null $error
     * @return \Illuminate\Http\JsonResponse
     */
    public function respondWithError($error = null)
    {
        $this->botResponse->setSuccess(false);
        if($error) {
            $this->botResponse->addMessage($error);
        }
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
