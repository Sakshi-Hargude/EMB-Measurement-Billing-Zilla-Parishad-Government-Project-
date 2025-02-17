<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = "activity_logs";

    protected $fillable = ['id', 'description', 'url', 'method', 'ip_address', 'user_agent', 'user_id', 'user', 'created_at', 'updated_at'];
}
