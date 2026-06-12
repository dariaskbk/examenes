// Direct link copy with robust fallback for non-secure (HTTP) contexts.
function copyDirectLink(url) {
    copyTextToClipboard(url)
        .then(() => AppToast.show('Enlace de acceso copiado al portapapeles', 'success', 2500))
        .catch(() => {
            Swal.fire({
                title: 'Copia el enlace de acceso',
                html: '<p style="font-size:.82rem;color:#64748B;margin-bottom:.5rem;">Selecciona el enlace y cópialo con <strong>Ctrl + C</strong>.</p>',
                input: 'text',
                inputValue: url,
                inputAttributes: { readonly: 'readonly', style: 'font-size:.8rem;text-align:center;' },
                showConfirmButton: true,
                confirmButtonText: 'Listo',
                confirmButtonColor: '#4F46E5',
                showCloseButton: true,
                customClass: { popup: 'swal-examcore' },
                didOpen: () => {
                    const inp = Swal.getInput();
                    if (inp) { inp.focus(); inp.select(); }
                }
            });
        });
}

function copyTextToClipboard(text) {
    if (navigator.clipboard && navigator.clipboard.writeText && window.isSecureContext) {
        return navigator.clipboard.writeText(text);
    }
    return new Promise((resolve, reject) => {
        try {
            const ta = document.createElement('textarea');
            ta.value = text;
            ta.setAttribute('readonly', '');
            ta.style.position = 'fixed';
            ta.style.top = '-9999px';
            ta.style.opacity = '0';
            document.body.appendChild(ta);
            ta.focus();
            ta.select();
            ta.setSelectionRange(0, text.length);
            const ok = document.execCommand('copy');
            document.body.removeChild(ta);
            ok ? resolve() : reject(new Error('execCommand failed'));
        } catch (e) {
            reject(e);
        }
    });
}
