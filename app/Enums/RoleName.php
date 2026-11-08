<?php

namespace App\Enums;

enum RoleName: string
{
  case USER = 'user';
  case SELLER = 'seller';
  case ADMIN = 'admin';
  case DELIVERY = 'delivery';

  public function label(): string
  {
    return match ($this) {
      self::USER => 'عميل',
      self::SELLER => 'بائع',
      self::ADMIN => 'مدير',
      self::DELIVERY => 'مندوب توصيل',
    };
  }
}
