<?php

namespace App\Models;

use App\Traits\FormatDateToSerialize;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Sanctum\HasApiTokens;

class User extends Model implements Authenticatable
{
    use HasApiTokens, HasFactory, \Illuminate\Auth\Authenticatable, FormatDateToSerialize, SoftDeletes;

    protected $fillable = [
        'username',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];
}
