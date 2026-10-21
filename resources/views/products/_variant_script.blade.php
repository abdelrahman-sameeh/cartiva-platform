<script>
  document.addEventListener('DOMContentLoaded', () => {
    const variantsList = document.getElementById('variants-list');
    const addVariantButton = document.getElementById('add-variant');
    if (!variantsList || !addVariantButton) return;

    const optionRow = (variantIndex, optionIndex) => `<div class="row g-2 variant-option-row"><div class="col-md-5"><input class="form-control" name="variants[${variantIndex}][options][${optionIndex}][key]" type="text" placeholder="اسم الاختيار، مثل السعة" required></div><div class="col-md-5"><input class="form-control" name="variants[${variantIndex}][options][${optionIndex}][value]" type="text" placeholder="القيمة، مثل 256GB" required></div><div class="col-md-2 d-grid"><button class="btn btn-outline-danger remove-variant-option" type="button">حذف</button></div></div>`;

    addVariantButton.addEventListener('click', () => {
      const index = Number(variantsList.dataset.nextIndex);
      variantsList.dataset.nextIndex = index + 1;
      variantsList.insertAdjacentHTML('beforeend', `<div class="product-variant-row border rounded-4 p-3" data-variant-index="${index}"><div class="row g-2 align-items-start"><div class="col-md-4"><label class="form-label small">SKU اختياري</label><input class="form-control" name="variants[${index}][sku]" type="text" placeholder="مثال: PHONE-256-BLK"></div><div class="col-md-3"><label class="form-label small">سعر مختلف اختياري</label><input class="form-control" name="variants[${index}][price]" type="number" min="0" step="0.01" placeholder="سعر المنتج الأساسي"></div><div class="col-md-3"><label class="form-label small">المخزون</label><input class="form-control" name="variants[${index}][stock]" type="number" min="0" value="0" required></div><div class="col-md-2 d-grid align-self-end"><button class="btn btn-outline-danger remove-variant" type="button">حذف النسخة</button></div></div><div class="mt-3"><div class="d-flex align-items-center justify-content-between gap-2 mb-2"><span class="small fw-semibold">اختيارات هذه النسخة</span><button class="btn btn-outline-primary btn-sm add-variant-option" type="button">إضافة اختيار</button></div><div class="variant-options-list d-grid gap-2" data-next-option-index="0"></div></div></div>`);
    });

    variantsList.addEventListener('click', (event) => {
      const variantRow = event.target.closest('.product-variant-row');
      if (!variantRow) return;
      if (event.target.closest('.add-variant-option')) {
        const list = variantRow.querySelector('.variant-options-list');
        const optionIndex = Number(list.dataset.nextOptionIndex);
        list.dataset.nextOptionIndex = optionIndex + 1;
        list.insertAdjacentHTML('beforeend', optionRow(variantRow.dataset.variantIndex, optionIndex));
        return;
      }
      event.target.closest('.remove-variant-option')?.closest('.variant-option-row')?.remove();
      if (event.target.closest('.remove-variant') && variantsList.querySelectorAll('.product-variant-row').length > 1) variantRow.remove();
    });
  });
</script>
