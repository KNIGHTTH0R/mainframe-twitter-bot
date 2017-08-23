<?php

namespace App\Http\Controllers;

use Aubruz\Mainframe\MainframeClient;
use Aubruz\Mainframe\UI\Button;
use Aubruz\Mainframe\UI\Modal;
use Aubruz\Mainframe\UI\Form;
use Illuminate\Http\Request;


class BotController extends ApiController
{
    private $mainframeClient;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->mainframeClient = new MainframeClient(env('BOT_SECRET'), 'https://api-staging.mainframe.com/bots/v1/');
    }

    public function index()
    {
        return $this->respondCreated(app()->environment());
    }

    public function hello (Request $request)
    {
        $conversationId = $request->input('conversation_id');
        if(is_null($conversationId)){
            $this->respondBadRequest();
        }
        $this->mainframeClient->sendMessage($conversationId, 'Hello World!!');
        return $this->respond(["success" => true]);
    }

    public function bye()
    {
        return $this->respond(["success" => true]);
    }

    public function post(Request $request)
    {
        if(!$request->has('context.user_id') || !$request->has('context.conversation_id') || !$request->has('context.subscription_token')) {
            $this->respondBadRequest();
        }

        $requestType = $request->input('data.type');

        switch($requestType){
            case 'login':
                break;
        }


        return $this->respond([
            'success'   => true,
            'message'   => 'Signin with your twitter account to get direct messages and/or tweets that mention your name.',
            'data'      =>
                (new Modal('Choose you subscription'))
                    ->addButton(new Button("Save", "save"))
                    ->addButton(new Button("Signin with Twitter", "signin", "secondary"))
                    ->setRender(
                        (new Form())
                        ->addTextInput("hashtags", "Enter the hashtags that you want to follow.", "#")
                        ->addTextInput("people", "Enter the people that you want to follow.", "@")
                        ->addData("hashtags", "#mainframe,#productivity")
                        ->addData("people", "@MainframeApp")
                    )->toArray()
        ]);
    }
}
