<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class JuniorEngineer extends Model
{
     /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable =[
       
        'division_name',
        'subdivision_name',
        'designation',
        'chargefrom',
        'chargeupto',
        'mobileno',
        'email',
        'username',
        'password'
    ];
}
