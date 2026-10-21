@extends('layouts.app')

@section('title', 'إدارة صلاحيات المستخدمين | ' . config('app.name'))

@section('content')
  <div class="container py-5">
    <div class="d-flex flex-column flex-md-row align-items-md-end justify-content-between gap-3 mb-4">
      <div>
        <a class="text-brand text-decoration-none fw-semibold" href="{{ route('dashboard') }}">العودة للحساب</a>
        <h1 class="fw-bold mt-2 mb-1">إدارة صلاحيات المستخدمين</h1>
        <p class="text-muted-custom mb-0">يمكن للمستخدم امتلاك أكثر من صلاحية في نفس الوقت.</p>
      </div>

      <a class="btn btn-outline-primary" href="{{ route('admin.categories.index') }}">إدارة التصنيفات</a>
    </div>

    <div class="alert alert-info border-0" role="alert">
      الحسابات الجديدة تبدأ بصلاحية «عميل» فقط. تعيين بائع أو مندوب أو مدير يتم من هذه الصفحة بواسطة Admin.
    </div>

    <form class="dashboard-card card border-0 mb-4" method="GET" action="{{ route('admin.users.roles.index') }}">
      <div class="card-body p-3">
        <div class="input-group">
          <input class="form-control" name="q" type="search" value="{{ $search }}"
            placeholder="ابحث بالاسم أو البريد الإلكتروني...">
          <button class="btn btn-brand px-4" type="submit">بحث</button>
          @if ($search !== '')
            <a class="btn btn-light" href="{{ route('admin.users.roles.index') }}">مسح</a>
          @endif
        </div>
      </div>
    </form>

    @if ($users->isEmpty())
      <div class="dashboard-card card border-0">
        <div class="card-body text-center p-5">
          <h2 class="h4 fw-bold">لا يوجد مستخدمون مطابقون</h2>
          <p class="text-muted-custom mb-0">جرّب البحث باسم أو بريد إلكتروني مختلف.</p>
        </div>
      </div>
    @else
      <div class="d-grid gap-3">
        @foreach ($users as $managedUser)
          <form class="dashboard-card card border-0" method="POST"
            action="{{ route('admin.users.roles.update', $managedUser) }}">
            @csrf
            @method('PUT')

            <div class="card-body p-4">
              <div class="row align-items-center g-3">
                <div class="col-lg-4">
                  <div class="d-flex align-items-center gap-3">
                    <span class="brand-mark flex-shrink-0">
                      {{ mb_strtoupper(mb_substr($managedUser->first_name, 0, 1)) }}
                    </span>
                    <div class="overflow-hidden">
                      <div class="fw-bold text-truncate">
                        {{ $managedUser->first_name }} {{ $managedUser->last_name }}
                        @if ($managedUser->is(auth()->user()))
                          <span class="badge text-bg-light border text-dark">أنت</span>
                        @endif
                      </div>
                      <small class="text-muted-custom d-block text-truncate">{{ $managedUser->email }}</small>
                    </div>
                  </div>
                </div>

                <div class="col-lg-6">
                  <div class="d-flex flex-wrap gap-3">
                    @foreach ($roles as $role)
                      <div class="form-check">
                        <input class="form-check-input" id="user-{{ $managedUser->id }}-role-{{ $role->id }}" name="role_ids[]"
                          type="checkbox" value="{{ $role->id }}" @checked($managedUser->roles->contains('id', $role->id))>
                        <label class="form-check-label" for="user-{{ $managedUser->id }}-role-{{ $role->id }}">
                          {{ $role->label }}
                        </label>
                      </div>
                    @endforeach
                  </div>
                  @error("roles.{$managedUser->id}")
                    <div class="text-danger small mt-2">{{ $message }}</div>
                  @enderror
                  @if ($errors->has('role_ids') || $errors->has('role_ids.*'))
                    <div class="text-danger small mt-2">يجب اختيار صلاحية واحدة على الأقل.</div>
                  @endif
                </div>

                <div class="col-lg-2 d-grid">
                  <button class="btn btn-brand" type="submit">حفظ</button>
                </div>
              </div>
            </div>
          </form>
        @endforeach
      </div>

      <div class="mt-4">
        {{ $users->links() }}
      </div>
    @endif
  </div>
@endsection
