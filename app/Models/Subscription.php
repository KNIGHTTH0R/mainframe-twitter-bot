<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Subscription
 * @package App\Model
 *
 * @property string $subscription_id
 * @property string $label
 * @property string $hashtags
 * @property string $people
 * @property string $twitter_oauth_token
 * @property string $twitter_oauth_token_secret
 * @property string $twitter_user_id
 * @property string $twitter_screen_name
 * @property-read Conversation $conversation
 */
class Subscription extends Model
{

    /**
     * @return BelongsTo
     */
    public function conversation()
    {
        return $this->belongsTo('App\Models\Conversation');
    }


}