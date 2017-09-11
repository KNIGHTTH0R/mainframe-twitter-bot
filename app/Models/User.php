<?php

namespace App\Models;

use Illuminate\Auth\Authenticatable;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;


/**
 * Class User
 * @package App\Models
 *
 * @property string $mainframe_user_id
 * @property string $twitter_oauth_token
 * @property string $twitter_oauth_token_secret
 * @property string $twitter_user_id
 * @property string $twitter_screen_name
 * @property integer $twitter_home_timeline_limit
 * @property integer $twitter_user_timeline_limit
 * @property integer $twitter_search_limit
 * @property integer $twitter_get_lists_limit
 * @property integer $twitter_show_list_limit
 * @property integer $twitter_limits_limit
 * @property-read Collection|Subscription[] $subscriptions
 * @property-read Collection|TwitterList[] $twitterLists
 */
class User extends Model implements AuthenticatableContract, AuthorizableContract
{
    use Authenticatable, Authorizable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'mainframe_user_id'
    ];

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
     * @return HasMany
     */
    public function twitterLists()
    {
        return $this->hasMany('App\Models\TwitterList');
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