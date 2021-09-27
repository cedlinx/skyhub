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
        'name', 
        'description', 
        'skydahid', 
        'assetid', 
        'type_id', 
        'user_id', 
        'transferable', 
        'file',
        'hash', 
        'location', 
        'lat', 
        'lng'
    ];
    
    public function user()
    {
        return $this->belongsTo(User::class)->withDefault([
            'name' => 'Unknown User',
        ]);
    }

    public function type()
    {
        return $this->belongsTo(Type::class)->withDefault([
            'Type' => 'Unknown',
        ]);
    }

    public function recoveries()
    {
        return $this->hasMany(Recovery::class);
    }

    public function transfers()
    {
        return $this->hasMany(Transfer::class);
    }

}
