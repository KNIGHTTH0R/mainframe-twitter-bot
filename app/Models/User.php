<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class Conversation
 * @package App\Model
 *
 * @property string $mainframe_user_id
 * @property string $twitter_oauth_token
 * @property string $twitter_oauth_token_secret
 * @property string $twitter_user_id
 * @property string $twitter_screen_name
 * @property-read Collection|Subscription[] $subscriptions
 */
class User extends Model
{

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'mainframe_user_id'
    ];

    /**
     * @return HasMany
     */
    public function subscriptions()
    {
        return $this->hasMany('App\Models\Subscription');
    }

}