// Universal Table Search - Add to pages with data-table
document.addEventListener('DOMContentLoaded', function() {
  const searchInputs = document.querySelectorAll('.card-header input[placeholder*="Search"]');
  searchInputs.forEach(input => {
    input.addEventListener('input', function() {
      const table = this.closest('.card').querySelector('.data-table');
      const rows = table.querySelectorAll('tbody tr');
      const term = this.value.toLowerCase();
      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(term) ? '' : 'none';
      });
    });
  });
});

