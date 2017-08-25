<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Conversation
 * @package App\Model
 *
 * @property string $conversation_id
 * @property-read Collection|Subscription[] $subscriptions
 */
class Conversation extends Model
{

    /**
     * @return HasMany
     */
    public function subscriptions()
    {
        return $this->hasMany('App\Models\Subscription');
    }

}