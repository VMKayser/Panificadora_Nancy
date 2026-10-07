<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class IdempotencyKey extends Model
{
    protected $table = 'idempotency_keys';

    protected $fillable = [
        'key', 'user_id', 'endpoint', 'request_hash', 'response_data'
    ];

    protected $casts = [
        'response_data' => 'array'
    ];
}
