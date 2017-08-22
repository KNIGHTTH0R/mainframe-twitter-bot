<?php

namespace App\Http\Controllers;

use Aubruz\Mainframe\Mainframe;
use Illuminate\Http\Request;

class ExampleController extends ApiController
{
    private $mainframeClient;
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->mainframeClient = new Mainframe(env('BOT_SECRET'));
    }

    public function index()
    {
        return $this->respondCreated(app()->environment());
    }

    public function hello(Request $request)
    {
        $conversationId = $request->input('conversation_id');
        $this->mainframeClient->sendMessage($conversationId, 'Hello World!!');
        return $this->respond(["success" => true]);
    }

    public function bye(Request $request)
    {
        $conversationId = $request->input('conversation_id');

        $this->mainframeClient->sendMessage($conversationId, 'Bye!');
        return $this->respond(["success" => true]);
    }
}
