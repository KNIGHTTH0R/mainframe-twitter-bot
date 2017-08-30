<?php

return [

    /**
     * To work with Twitter's Streaming API you'll need some credentials.
     *
     * If you don't have credentials yet, head over to https://apps.twitter.com/
     */

    'access_token' => env('TWITTER_API_KEY'),

    'access_token_secret' => env('TWITTER_API_SECRET'),

    'consumer_key' => env('TWITTER_CONSUMER_KEY'),

    'consumer_secret' => env('TWITTER_CONSUMER_SECRET'),
];