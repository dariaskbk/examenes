// Excel / ZIP import UI.
const IMPORT_ALLOWED_EXT = ['xlsx', 'xls', 'zip'];

function updateImportBtn() {
    const input   = document.getElementById('importFileInput');
    const btn     = document.getElementById('importBtn');
    const info    = document.getElementById('importFileInfo');
    const name    = document.getElementById('importFileName');
    const zipHint = document.getElementById('importZipHint');

    if (input.files.length > 0) {
        const file    = input.files[0];
        const ext     = file.name.toLowerCase().split('.').pop();
        const isZip   = ext === 'zip';

        if (!IMPORT_ALLOWED_EXT.includes(ext)) {
            btn.disabled          = true;
            info.style.display    = '';
            info.style.color      = '#DC2626';
            name.textContent      = `❌ Formato no permitido (.${ext}). Solo .xlsx, .xls o .zip.`;
            zipHint.style.display = 'none';
            input.value           = '';
            return;
        }

        btn.disabled = false;
        const sizeStr = file.size > 1048576
            ? (file.size / 1048576).toFixed(1) + ' MB'
            : (file.size / 1024).toFixed(0) + ' KB';
        name.textContent      = file.name + ' (' + sizeStr + ')';
        info.style.color      = '#059669';
        info.style.display    = '';
        zipHint.style.display = isZip ? '' : 'none';
    } else {
        btn.disabled = true;
        info.style.display    = 'none';
        zipHint.style.display = 'none';
    }
}

function startImport(form) {
    const input = document.getElementById('importFileInput');
    if (!input.files.length) return false;

    const ext = input.files[0].name.toLowerCase().split('.').pop();

    if (!IMPORT_ALLOWED_EXT.includes(ext)) {
        Swal.fire({
            icon: 'error',
            title: 'Formato no permitido',
            text: 'Solo se aceptan archivos .xlsx, .xls o .zip con la plantilla de SICORE.',
            confirmButtonColor: '#4F46E5',
        });
        return false;
    }

    const isZip    = ext === 'zip';
    const fileArea = document.getElementById('importFileArea');
    const loading  = document.getElementById('importLoading');
    const loadMsg  = document.getElementById('importLoadingMsg');
    const btn      = document.getElementById('importBtn');
    fileArea.style.display = 'none';
    loading.style.display  = '';
    btn.style.display      = 'none';
    if (loadMsg) loadMsg.textContent = isZip ? 'Procesando ZIP con multimedia…' : 'Procesando Excel…';
    form.querySelector('[type=submit]') && (form.querySelector('[type=submit]').disabled = true);
    AppLoader.show(
        isZip ? 'Importando con multimedia…' : 'Importando preguntas…',
        isZip ? 'Extrayendo ZIP y procesando archivos. Esto puede tardar según el tamaño.' : 'Procesando el archivo Excel, por favor espera.'
    );
}
