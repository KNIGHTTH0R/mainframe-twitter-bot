<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Subscription
 * @package App\Models
 *
 * @property string $conversation_id
 * @property string $user_id
 * @property string $mainframe_subscription_id
 * @property string $label
 * @property string $search
 * @property string $people
 * @property string $hashtags_since_id
 * @property string $people_since_id
 * @property string $mention_since_id
 * @property string $timeline_since_id
 * @property boolean $get_my_timeline
 * @property boolean $get_my_mention
 * @property boolean $get_people_retweets
 * @property boolean $get_people_replies
 * @property-read User $user
 * @property-read Conversation $conversation
 * @property-read TwitterList $list
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

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    /**
     * @return BelongsTo
     */
    public function twitterList()
    {
        return $this->belongsTo('App\Models\TwitterList');
    }

}