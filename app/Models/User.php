<?php

namespace App\Models;

use App\Traits\LogsModelActions;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Joelwmale\LaravelEncryption\Traits\EncryptsAttributes;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Jetstream\HasProfilePhoto;
use Laravel\Jetstream\HasTeams;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasApiTokens, HasRoles, HasFactory, HasProfilePhoto, HasTeams, Notifiable, TwoFactorAuthenticatable, LogsModelActions, EncryptsAttributes;

    protected static $logAttributes = ['status', 'amount', 'due_date'];
    protected static $logOnlyDirty = true;
    protected static $logName = 'user';
    /**
     * The users that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'user_type',
        'email',
        'business_name',
        'phone_number',
        'department_id',
        'is_manager',
        'country_id',
        'state_id',
        'city_id',
        'email_verified_at',
        'password',
        'current_team_id',
        'profile_photo_path',
    ];

    protected $encryptableAttributes = [
        'first_name',
        'last_name',
        'email',
        'business_name',
        'phone_number',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    /**
     * The accessors to append to the model's array form.
     *
     * @var array<int, string>
     */
    protected $appends = [
        'profile_photo_url',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    // ==========================
    // Relationships
    // ==========================

    public function customerCreditLimit()
    {
        return $this->hasOne(CustomerCreditLimit::class, 'user_id');
    }

    public function country()
    {
        return $this->belongsTo(Country::class);
    }

    public function state()
    {
        return $this->belongsTo(State::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }
    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'user_id', 'id');
    }

    public function shop()
    {
        return $this->hasOne(ShopSetting::class, 'user_id', 'id');
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function coupons()
    {
        return $this->hasMany(Coupon::class);
    }

    public function pickupPoint()
    {
        return $this->hasMany(PickupPoint::class);
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function sellerOrders()
    {
        return $this->hasMany(Order::class, 'seller_id');
    }

    public function transactions()
    {
        return $this->hasMany(Transaction::class, 'user_id');
    }

    public function sales()
    {
        return $this->hasMany(Transaction::class, 'seller_id');
    }

    public function supportTickets()
    {
        return $this->hasMany(SupportTicket::class, 'user_id');
    }

    public function userSchedulePayment()
    {
        return $this->hasMany(SchedulePayment::class, 'user_id');
    }

    public function sellerSchedulePayment()
    {
        return $this->hasMany(SchedulePayment::class, 'seller_id');
    }

    public function paymentsMade()
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentsReceived()
    {
        return $this->hasMany(Payment::class, 'seller_id');
    }

    public function productWishlists()
    {
        return $this->hasMany(ProductWishlist::class);
    }

    public function sellerWishlists()
    {
        return $this->hasMany(ProductWishlist::class, 'seller_id');
    }

    public function userRefund()
    {
        return $this->hasMany(RefundRequest::class);
    }

    public function sellerRefund()
    {
        return $this->hasMany(RefundRequest::class, 'seller_id');
    }

    public function userWallet()
    {
        return $this->hasMany(Wallet::class, 'user_id');
    }

    public function sellerWallet()
    {
        return $this->hasMany(Wallet::class, 'seller_id');
    }

    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id');
    }

    public function transferRequestsSent()
    {
        return $this->hasMany(TransferRequest::class, 'from_user_id');
    }

    public function transferRequestsReceived()
    {
        return $this->hasMany(TransferRequest::class, 'to_user_id');
    }

    public function deviceTokens()
    {
        return $this->hasMany(DeviceToken::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
}
