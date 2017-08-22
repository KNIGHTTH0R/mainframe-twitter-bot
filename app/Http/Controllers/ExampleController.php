<?php

namespace App\Http\Controllers;

use Aubruz\Mainframe\Mainframe;
use Illuminate\Http\Request;

class ExampleController extends ApiController
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    public function index()
    {
        return $this->respondCreated(app()->environment());
    }

    public function hello(Request $request)
    {
        $conversationId = $request->input('conversation_id');
        $mainframeClient = new Mainframe(config('app.bot_key'));
        $mainframeClient->sendMessage($conversationId, 'Hello World!');
        return $this->respond([
            'conversation_id' => $conversationId,
            'message' => 'Hello World!'
        ]);
    }
}
