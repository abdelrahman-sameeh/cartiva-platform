<script>
  document.addEventListener('DOMContentLoaded', () => {
    const variantsList = document.getElementById('variants-list');
    const addVariantButton = document.getElementById('add-variant');
    if (!variantsList || !addVariantButton) return;

    const optionRow = (variantIndex, optionIndex) => `
      <div class="variant-option-row">
        <div><label class="visually-hidden" for="variant-${variantIndex}-option-${optionIndex}-key">اسم الاختيار</label><input class="form-control" id="variant-${variantIndex}-option-${optionIndex}-key" name="variants[${variantIndex}][options][${optionIndex}][key]" type="text" placeholder="الاختيار، مثل اللون" required></div>
        <div><label class="visually-hidden" for="variant-${variantIndex}-option-${optionIndex}-value">قيمة الاختيار</label><input class="form-control" id="variant-${variantIndex}-option-${optionIndex}-value" name="variants[${variantIndex}][options][${optionIndex}][value]" type="text" placeholder="القيمة، مثل أسود" required></div>
        <button class="btn btn-icon-danger remove-variant-option" type="button" aria-label="حذف الاختيار"><i class="bi bi-x-lg" aria-hidden="true"></i></button>
      </div>`;

    const variantRow = index => `
      <article class="product-variant-row" data-variant-index="${index}">
        <header class="variant-row-heading">
          <div><span class="variant-number"></span><div><strong class="variant-title"></strong><small>حدد المخزون وأي اختلاف في السعر أو الاختيارات</small></div></div>
          <button class="btn btn-icon-danger remove-variant" type="button" aria-label="حذف النسخة"><i class="bi bi-trash3" aria-hidden="true"></i></button>
        </header>
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label" for="variant-${index}-stock">المخزون <span class="required-mark">*</span></label><input class="form-control" id="variant-${index}-stock" name="variants[${index}][stock]" type="number" min="0" value="0" required><div class="form-text">عدد القطع المتاحة من هذه النسخة.</div></div>
          <div class="col-md-4"><label class="form-label" for="variant-${index}-sku">SKU <span class="optional-label">اختياري</span></label><input class="form-control" id="variant-${index}-sku" name="variants[${index}][sku]" type="text" placeholder="مثال: MIXER-BLK"><div class="form-text">كود داخلي لتمييز النسخة.</div></div>
          <div class="col-md-4"><label class="form-label" for="variant-${index}-price">سعر النسخة <span class="optional-label">اختياري</span></label><div class="input-group"><input class="form-control" id="variant-${index}-price" name="variants[${index}][price]" type="number" min="0" step="0.01" placeholder="السعر الأساسي"><span class="input-group-text">جنيه</span></div></div>
        </div>
        <div class="variant-options-panel">
          <div class="variant-options-heading"><div><strong>اختيارات النسخة</strong><small>مثال: اللون = أسود، أو السعة = 256GB</small></div><button class="btn btn-outline-primary btn-sm add-variant-option" type="button"><i class="bi bi-plus-lg" aria-hidden="true"></i> إضافة اختيار</button></div>
          <div class="variant-options-list" data-next-option-index="0"><div class="variant-options-empty">لا توجد اختيارات — هذه هي النسخة الأساسية للمنتج.</div></div>
        </div>
      </article>`;

    const syncVariantRows = () => {
      const rows = variantsList.querySelectorAll('.product-variant-row');
      rows.forEach((row, index) => {
        row.querySelector('.variant-number').textContent = index + 1;
        row.querySelector('.variant-title').textContent = `نسخة ${index + 1}`;
        row.querySelector('.remove-variant').disabled = rows.length === 1;
      });
    };

    const syncOptionsEmptyState = list => {
      const emptyState = list.querySelector('.variant-options-empty');
      if (list.querySelector('.variant-option-row')) {
        emptyState?.remove();
      } else if (!emptyState) {
        list.insertAdjacentHTML('beforeend', '<div class="variant-options-empty">لا توجد اختيارات — هذه هي النسخة الأساسية للمنتج.</div>');
      }
    };

    addVariantButton.addEventListener('click', () => {
      const index = Number(variantsList.dataset.nextIndex);
      variantsList.dataset.nextIndex = index + 1;
      variantsList.insertAdjacentHTML('beforeend', variantRow(index));
      syncVariantRows();
      document.getElementById(`variant-${index}-stock`).focus();
    });

    variantsList.addEventListener('click', event => {
      const row = event.target.closest('.product-variant-row');
      if (!row) return;

      if (event.target.closest('.add-variant-option')) {
        const list = row.querySelector('.variant-options-list');
        const optionIndex = Number(list.dataset.nextOptionIndex);
        list.dataset.nextOptionIndex = optionIndex + 1;
        list.querySelector('.variant-options-empty')?.remove();
        list.insertAdjacentHTML('beforeend', optionRow(row.dataset.variantIndex, optionIndex));
        document.getElementById(`variant-${row.dataset.variantIndex}-option-${optionIndex}-key`).focus();
        return;
      }

      const removeOptionButton = event.target.closest('.remove-variant-option');
      if (removeOptionButton) {
        const list = row.querySelector('.variant-options-list');
        removeOptionButton.closest('.variant-option-row')?.remove();
        syncOptionsEmptyState(list);
        return;
      }

      if (event.target.closest('.remove-variant') && variantsList.querySelectorAll('.product-variant-row').length > 1) {
        row.remove();
        syncVariantRows();
      }
    });

    syncVariantRows();
  });
</script>
