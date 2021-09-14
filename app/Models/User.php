<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

//COA: added to handle forgot/reset password
use Laravel\Passport\HasApiTokens;
use Illuminate\Auth\Passwords\CanResetPassword as canReset;   //NOTE the difference between this and the next line. the next is an interface, this is a Trait. //I added "as canReset" to avoid a conflict
use Illuminate\Contracts\Auth\CanResetPassword;     //Interface

//COA: Email verification
use Illuminate\Contracts\Auth\MustVerifyEmail as mustVerify;    //interface


class User extends Authenticatable implements CanResetPassword, mustVerify
{
    use HasFactory, HasApiTokens, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'avatar', 
        'provider_id', 
        'provider',
        'access_token',
        'address',
        'phone',
        'email_verified_at',
        'email_verified'
    ];
    
    protected $guarded = ['*'];
    
    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function sendPasswordResetNotification($token)
    { 
        //very important that these 2 variables get passed to the __construct function IN MailResetPasswordNotification (in the same order)
        $this->notify(new \App\Notifications\MailResetPasswordNotification($token, $this->email));
    }

    public function sendEmailVerificationNotification()
    {
      //  $email = 'cedlinx@yahoo.com';
      //  $this->notify(new \App\Notifications\MailVerifyEmailNotification($email));
      $this->notify(new \Illuminate\Auth\Notifications\VerifyEmail());
    }

    public function assets()
    {
        return $this->hasMany(Asset::class);
    }

    public function type()
    {
        return $this->belongsTo(Type::class)->withDefault([
            'type' => 'Unknown',
        ]);
    }

    public function recoveries()
    {
        return $this->hasMany(Recovery::class);
    }
}


