<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserToken extends Model
{
    use HasFactory;


    public static $GHL = 'ghl';
    public static $CLOUDBEDS = 'cloudbeds';
    public static $DEFAULT_USER_ID = 1;

    protected $fillable = ['user_id', 'type', 'property_id', 'code', 'access_token', 'refresh_token'];
}
