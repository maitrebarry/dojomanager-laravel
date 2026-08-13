{{-- Requires: $baseUrl (string) — base URL for the resource, e.g. url('admin/salles') --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterForm = document.getElementById('filterForm');
    let filterTimer = null;

    if (filterForm) {
        filterForm.querySelectorAll('.js-auto-filter').forEach(input => {
            input.addEventListener('change', () => filterForm.submit());
        });
        filterForm.querySelectorAll('.js-auto-filter-text').forEach(input => {
            input.addEventListener('input', () => {
                window.clearTimeout(filterTimer);
                filterTimer = window.setTimeout(() => filterForm.submit(), 500);
            });
        });
    }

    const deleteModalEl = document.getElementById('deleteModal');
    if (deleteModalEl) {
        const deleteModal = new bootstrap.Modal(deleteModalEl);
        const deleteBaseUrl = "{{ $baseUrl }}";
        document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('deleteName').textContent = this.dataset.name || '';
                document.getElementById('deleteForm').action = `${deleteBaseUrl}/${this.dataset.id}`;
                deleteModal.show();
            });
        });
    }
});
</script>
