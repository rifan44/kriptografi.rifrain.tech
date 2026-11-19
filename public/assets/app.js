document.addEventListener('DOMContentLoaded', () => {
  const tabs = [...document.querySelectorAll('.source-tab')];
  const panels = [...document.querySelectorAll('.source-panel')];
  const fileInput = document.querySelector('#private-key-file');
  const pasteInput = document.querySelector('#private-key-text');
  const fileName = document.querySelector('#file-name');
  const setSource = (source) => {
    tabs.forEach((tab) => tab.classList.toggle('active', tab.dataset.source === source));
    panels.forEach((panel) => panel.classList.toggle('hidden', panel.dataset.panel !== source));
    if (fileInput) fileInput.required = source === 'upload';
    if (pasteInput) pasteInput.required = source === 'paste';
  };
  tabs.forEach((tab) => tab.addEventListener('click', () => setSource(tab.dataset.source)));
  if (fileInput) fileInput.addEventListener('change', () => {
    if (fileName) fileName.textContent = fileInput.files?.[0]?.name || 'Belum ada file dipilih';
  });
  if (tabs.length) setSource('upload');

  document.querySelectorAll('[data-filter-input]').forEach((input) => {
    const group = input.dataset.filterInput;
    const select = document.querySelector(`[data-filter-select="${group}"]`);
    const items = [...document.querySelectorAll(`[data-filter-item="${group}"]`)];
    const counter = document.querySelector(`[data-filter-count="${group}"]`);
    const update = () => {
      const query = input.value.trim().toLocaleLowerCase('id');
      const selected = select?.value || '';
      let visible = 0;
      items.forEach((item) => {
        const matchesText = !query || (item.dataset.search || '').includes(query);
        const matchesSelect = !selected || item.dataset.status === selected;
        item.hidden = !(matchesText && matchesSelect);
        if (!item.hidden) visible += 1;
      });
      if (counter) counter.textContent = `${visible} ${group === 'project' ? 'proyek' : 'aktivitas'}`;
    };
    input.addEventListener('input', update);
    select?.addEventListener('change', update);
  });

  document.querySelector('[data-export-projects]')?.addEventListener('click', () => {
    const rows = [...document.querySelectorAll('#project-table tr')].filter((row) => !row.hidden);
    const csv = rows.map((row) => [...row.cells].map((cell) => {
      const value = cell.innerText.trim().replace(/\s+/g, ' ');
      return `"${value.replaceAll('"', '""')}"`;
    }).join(',')).join('\r\n');
    const blob = new Blob(['\uFEFF', csv], { type: 'text/csv;charset=utf-8' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = 'rifrain-tech-proyek-demo.csv';
    document.body.append(link);
    link.click();
    link.remove();
    URL.revokeObjectURL(url);
  });
});
