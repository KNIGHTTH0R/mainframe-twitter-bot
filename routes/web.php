<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

$app->get('/', 'BotController@index');
$app->post('/conversation_added', 'BotController@hello');
$app->post('/conversation_removed', 'BotController@bye');
$app->post('/post', 'BotController@post');

