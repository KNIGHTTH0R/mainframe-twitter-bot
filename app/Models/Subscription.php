<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class Subscription
 * @package App\Model
 *
 * @property string $conversation_id
 * @property string $user_id
 * @property string $mainframe_subscription_id
 * @property string $label
 * @property string $hashtags
 * @property string $people
 * @property-read User $user
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

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}