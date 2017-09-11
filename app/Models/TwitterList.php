<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Class TwitterList
 * @package App\Models
 *
 * @property integer $id
 * @property string $twitter_id
 * @property string $twitter_slug
 * @property string $twitter_name
 * @property string $twitter_list_since_id
 * @property integer $user_id
 * @property-read Collection|Subscription[] $subscriptions
 * @property-read User $user
 */
class TwitterList extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * @return HasMany
     */
    public function subscriptions()
    {
        return $this->hasMany('App\Models\Subscription');
    }

    /**
     * @return BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

    public function resetTwitterData(){
        $this->twitter_oauth_token = null;
        $this->twitter_oauth_token_secret = null;
        $this->twitter_screen_name = null;
        $this->twitter_user_id = null;
        $this->twitter_home_timeline_limit = 0;
        $this->twitter_user_timeline_limit = 0;
        $this->twitter_search_limit = 0;
        $this->twitter_get_lists_limit = 0;
        $this->twitter_show_list_limit = 0;
        $this->twitter_limits_limit = 0;
    }

}