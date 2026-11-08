<?php

namespace App\Exceptions;

use App\Models\Product;
use RuntimeException;

class ProductAlreadyExistsException extends RuntimeException
{
  public function __construct(public readonly Product $product)
  {
    parent::__construct('product already exist');
  }
}