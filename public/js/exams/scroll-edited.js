// Auto-scroll + highlight on the edited question (after AJAX-free update).
(function () {
    function scrollToEditedQuestion() {
        // Source: URL fragment (#q-123) set by the controller redirect.
        let id = (window.location.hash || '').replace('#q-', '');
        if (!id) return;
        const el = document.getElementById('q-' + id);
        if (!el) return;

        requestAnimationFrame(() => {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
            const prevTransition = el.style.transition;
            const prevBg         = el.style.background;
            const prevBox        = el.style.boxShadow;
            el.style.transition  = 'background .25s, box-shadow .25s';
            el.style.background  = '#FEF3C7';
            el.style.boxShadow   = '0 0 0 3px #F59E0B';
            setTimeout(() => {
                el.style.background = prevBg;
                el.style.boxShadow  = prevBox;
                setTimeout(() => { el.style.transition = prevTransition; }, 300);
            }, 1100);
        });

        // Clean the fragment from the URL so a refresh doesn't keep highlighting.
        history.replaceState(null, '', window.location.pathname + window.location.search);
    }

    if (document.readyState === 'complete' || document.readyState === 'interactive') {
        scrollToEditedQuestion();
    } else {
        document.addEventListener('DOMContentLoaded', scrollToEditedQuestion);
    }
})();
