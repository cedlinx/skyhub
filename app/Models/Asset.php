<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Asset extends Model
{
    use HasFactory, SoftDeletes;
    
    protected $dates = ['deleted_at'];
    protected $fillable = [
        'name', 'description', 'skydahid', 'assetid', 'category_id', 'user_id'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class)->withDefault([
            'name' => 'Unknown User',
        ]);
    }

    public function category()
    {
        return $this->belongsTo(Category::class)->withDefault([
            'category' => 'Unknown',
        ]);
    }

    public function recoveries()
    {
        return $this->hasMany(Recovery::class);
    }

}
