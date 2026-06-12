// PDF section filter: append ?section_id=X to each PDF dropdown link.
(function () {
    const sel = document.getElementById('pdfSectionFilter');
    if (!sel) return;
    const links = document.querySelectorAll('.pdf-link');
    const baseUrls = {};
    links.forEach(a => { baseUrls[a.dataset.format] = a.getAttribute('href'); });

    function applyFilter() {
        const secId = sel.value;
        const label = secId ? sel.options[sel.selectedIndex].textContent : 'Todas las secciones';
        const header = document.getElementById('pdfHeaderLabel');
        if (header) header.textContent = 'PDF — ' + label;
        links.forEach(a => {
            const base = baseUrls[a.dataset.format];
            a.setAttribute('href', base + (secId ? (base.includes('?') ? '&' : '?') + 'section_id=' + secId : ''));
        });
    }
    sel.addEventListener('change', applyFilter);
    applyFilter();
})();
