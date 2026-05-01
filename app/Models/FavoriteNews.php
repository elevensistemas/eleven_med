<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FavoriteNews extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'link',
        'image_url',
        'description',
        'extended_text',
        'source',
        'pub_date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
