<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asset extends Model
{
    use HasFactory;
    protected $fillable = [
        'name', 'description', 'skydahid', 'assetid', 'type_id', 'user_id'
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
            'type' => 'Unknown',
        ]);
    }
}
