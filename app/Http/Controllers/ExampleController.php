<?php

namespace App\Http\Controllers;


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
}
