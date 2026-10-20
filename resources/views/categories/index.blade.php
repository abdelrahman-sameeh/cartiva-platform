@extends('layouts.app')

@section('title', 'إدارة التصنيفات | printore')

@section('content')
  <div class="container py-5">
    <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
      <div>
        <a class="text-brand text-decoration-none fw-semibold" href="{{ route('dashboard') }}">العودة للحساب</a>
        <h1 class="fw-bold mt-2 mb-1">إدارة التصنيفات</h1>
        <p class="text-muted-custom mb-0">أنشئ التصنيفات والتصنيفات الفرعية التي سيختار منها البائعون.</p>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-lg-5">
        <div class="dashboard-card card border-0 h-100">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">إنشاء تصنيف رئيسي</h2>
            <form method="POST" action="{{ route('admin.categories.store') }}">
              @csrf
              <label class="form-label fw-semibold" for="category_title">اسم التصنيف</label>
              <input class="form-control @error('title') is-invalid @enderror" id="category_title" name="title"
                type="text" value="{{ old('title') }}" required>
              @error('title')
                <div class="invalid-feedback">{{ $message }}</div>
              @enderror
              <button class="btn btn-brand w-100 mt-3" type="submit">إنشاء التصنيف</button>
            </form>
          </div>
        </div>
      </div>

      <div class="col-lg-7">
        <div class="dashboard-card card border-0 h-100">
          <div class="card-body p-4">
            <h2 class="h5 fw-bold mb-3">إنشاء تصنيف فرعي</h2>
            <form method="POST" action="{{ route('admin.sub-categories.store') }}">
              @csrf
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label fw-semibold" for="sub_category_title">اسم التصنيف الفرعي</label>
                  <input class="form-control" id="sub_category_title" name="title" type="text" value="{{ old('title') }}"
                    required>
                </div>
                <div class="col-md-6">
                  <label class="form-label fw-semibold" for="category_id">التصنيف الرئيسي</label>
                  <select class="form-select" id="category_id" name="category_id" required>
                    <option value="">اختر تصنيفًا</option>
                    @foreach ($categories as $category)
                      <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>
                        {{ $category->title }}
                      </option>
                    @endforeach
                  </select>
                </div>
              </div>
              <button class="btn btn-brand mt-3" type="submit">إنشاء التصنيف الفرعي</button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="dashboard-card card border-0 mt-4">
      <div class="card-body p-0">
        <h2 class="h5 fw-bold mb-3">التصنيفات الحالية</h2>
        @if ($categories->isEmpty())
          <p class="text-muted-custom mb-0">لا توجد تصنيفات حتى الآن.</p>
        @else
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="px-4">التصنيف الرئيسي</th>
                  <th>التصنيف الفرعي</th>
                  <th class="text-end px-4">الإجراء</th>
                </tr>
              </thead>
              <tbody>
                @foreach ($categories as $category)
                  <tr>
                    <td class="px-4 fw-bold" rowspan="{{ max($category->sub_categories->count(), 1) }}">
                      {{ $category->title }}
                    </td>
                    @if ($category->sub_categories->isNotEmpty())
                      <td>{{ $category->sub_categories->first()->title }}</td>
                      <td class="text-end px-4">
                        <form method="POST"
                          action="{{ route('admin.sub-categories.destroy', $category->sub_categories->first()) }}">
                          @csrf
                          @method('DELETE')
                          <button class="btn btn-outline-danger" type="submit">حذف الفرعي</button>
                        </form>
                      </td>
                    @else
                      <td class="text-muted-custom">لا توجد تصنيفات فرعية</td>
                      <td></td>
                    @endif
                  </tr>
                  @foreach ($category->sub_categories->skip(1) as $subCategory)
                    <tr>
                      <td>{{ $subCategory->title }}</td>
                      <td class="text-end px-4">
                        <form method="POST" action="{{ route('admin.sub-categories.destroy', $subCategory) }}">
                          @csrf
                          @method('DELETE')
                          <button class="btn btn-outline-danger" type="submit">حذف الفرعي</button>
                        </form>
                      </td>
                    </tr>
                  @endforeach
                  <tr>
                    <td colspan="2" class="text-end px-4 border-bottom-0">
                      <form method="POST" action="{{ route('admin.categories.destroy', $category) }}">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger" type="submit">حذف التصنيف بالكامل</button>
                      </form>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        @endif
      </div>
    </div>
  </div>
@endsection