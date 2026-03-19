<?php

namespace App\Models;

use App\Models\Address;
use App\Models\Cart;
use App\Models\Order;
use App\Models\Review;
use App\Models\Wallet;
use App\Models\Wishlist;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable, HasApiTokens, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'phone',
        'avatar',
        'provider_id',
        'email_verified_at',
        'phone_verified_at',
        'verification_code',
        'verification_sent_at',
        'referral_code',
        'user_type',
        'banned',
        'balance',
        'club_points',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'verification_sent_at' => 'datetime',
        'banned' => 'boolean',
        'balance' => 'decimal:2',
        'club_points' => 'integer',
    ];

    public function getFirstNameAttribute(): string
    {
        $name = trim((string) $this->name);
        if ($name === '') {
            return '';
        }

        return explode(' ', preg_replace('/\s+/', ' ', $name))[0] ?? '';
    }

    public function wishlists()
    {
        return $this->hasMany(Wishlist::class);
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function wallets()
    {
        return $this->hasMany(Wallet::class)->orderBy('created_at', 'desc');
    }

    public function carts()
    {
        return $this->hasMany(Cart::class);
    }

    public function reviews()
    {
        return $this->hasMany(Review::class);
    }

    public function addresses()
    {
        return $this->hasMany(Address::class);
    }

    public function shop()
    {
        return $this->belongsTo(Shop::class, 'shop_id');
    }

    public function followed_shops()
    {
        return $this->belongsToMany(Shop::class, 'shop_followers', 'user_id', 'shop_id');
    }

    public function chat_thread()
    {
        return $this->hasOne(ChatThread::class);
    }

    public function conversations()
    {
        return $this->hasMany(Conversation::class,'sender_id','id');
    }

    public function affiliate_user()
    {
        return $this->hasOne(AffiliateUser::class);
    }
    public function affiliate_withdraw_request()
    {
        return $this->hasMany(AffiliateWithdrawRequest::class);
    }
    public function affiliate_log()
    {
        return $this->hasMany(AffiliateLog::class);
    }

    public function delivery_boy(){
        return $this->hasOne(DeliveryBoy::class);
    }

    public function authCodes()
    {
        return $this->hasMany(AuthCode::class);
    }
}
