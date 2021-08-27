<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyCode extends Model
{
    protected $fillable = [
        'company_id', 'code',
    ];
}
