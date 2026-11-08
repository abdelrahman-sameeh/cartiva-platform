<?php

namespace App\Http\Requests\Admin;

use App\Enums\RoleName;
use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRolesRequest extends FormRequest
{
  public function authorize(): bool
  {
    return $this->user()?->hasRole(RoleName::ADMIN) ?? false;
  }

  public function rules(): array
  {
    return [
      'role_ids' => ['required', 'array', 'min:1'],
      'role_ids.*' => ['required', 'integer', 'distinct', 'exists:roles,id'],
    ];
  }
}
