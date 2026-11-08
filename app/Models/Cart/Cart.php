<?php

namespace App\Models\Cart;

use App\Models\Cart\CartItem;
use App\Models\Coupon;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
  use HasFactory;

  protected $fillable = ['user_id'];

  public function user(){
    return $this->belongsTo(User::class);
  }

  public function items(){
    return $this->hasMany(CartItem::class, 'cart_id');
  }

  public function coupons(){
    return $this->hasMany(CartCoupon::class);
  }

}
