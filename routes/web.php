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

// BOT ENDPOINTS FOR MAINFRAME API
$app->post('/conversation_added', 'BotController@conversationAdded');
$app->post('/conversation_removed', 'BotController@conversationRemoved');
$app->post('/post', 'BotController@post');
$app->post('/delete_subscription', 'BotController@deleteSubscription');
$app->post('/enable', 'BotController@enable');
$app->post('/disable', 'BotController@disable');

// BOT ENDPOINTS FOR TWITTER API
$app->get('/oauth/request_token', 'TwitterController@requestToken');
$app->get('/webhook/twitter', 'TwitterController@crcCheck');
$app->post('/webhook/twitter', 'TwitterController@webhook');