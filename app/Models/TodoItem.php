<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TodoItem extends Model
{
    protected $fillable = ['business_id', 'user_id', 'activity', 'activity_date', 'is_completed'];

    protected function casts(): array
    {
        return ['activity_date' => 'date', 'is_completed' => 'boolean'];
    }
}
