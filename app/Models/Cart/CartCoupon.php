<?php

namespace App\Models\Cart;

use App\Models\Coupon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartCoupon extends Model
{
  use HasFactory;

  protected $fillable = [
    'cart_id',
    'coupon_id',
  ];

  public function cart()
  {
    return $this->belongsTo(Cart::class, 'cart_id');
  }

  public function coupon()
  {
    return $this->belongsTo(Coupon::class, 'coupon_id');
  }


}

