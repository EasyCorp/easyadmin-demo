import './styles/admin.css';

import hljs from 'highlight.js/lib/core';
import css from 'highlight.js/lib/languages/css';
import php from 'highlight.js/lib/languages/php';
import twig from 'highlight.js/lib/languages/twig';
import xml from 'highlight.js/lib/languages/xml';
import yaml from 'highlight.js/lib/languages/yaml';

hljs.registerLanguage('css', css);
hljs.registerLanguage('php', php);
// the Twig grammar uses the XML one to highlight the HTML code of the templates
hljs.registerLanguage('xml', xml);
hljs.registerLanguage('twig', twig);
hljs.registerLanguage('yaml', yaml);

let sourceCodeRequest = null;

document.addEventListener('show.bs.modal', (event) => {
    if ('source-code-modal' !== event.target.id) {
        return;
    }

    const sourceCodeUrl = event.relatedTarget?.dataset.sourceCodeUrl;
    if (undefined === sourceCodeUrl) {
        highlightSourceCode(event.target);
        enableKeyboardTabNavigation(event.target);

        return;
    }

    event.target.querySelector('.modal-body-content h4').textContent = event.relatedTarget.dataset.sourceCodeTitle;
    loadSourceCode(event.target, sourceCodeUrl);
});

document.addEventListener('shown.bs.modal', (event) => {
    if ('source-code-modal' === event.target.id) {
        scrollToMarkedLines(event.target);
    }
});

// hidden panes have no height, so their code can only be scrolled once their tab is shown
document.addEventListener('shown.bs.tab', (event) => {
    const pane = document.querySelector(event.target.dataset.bsTarget);
    // only the first time, so going back to a file keeps the position where it was left
    if (null !== pane && null !== pane.closest('#source-code-modal') && undefined === pane.dataset.scrolledToMarks) {
        pane.dataset.scrolledToMarks = '';
        scrollToMarkedLines(pane);
    }
});

async function loadSourceCode(modal, url) {
    const files = modal.querySelector('.source-code-files');
    const error = modal.querySelector('.source-code-error');

    // a slow response must not replace the code of a feature opened later
    sourceCodeRequest?.abort();
    const request = new AbortController();
    sourceCodeRequest = request;

    files.replaceChildren();
    files.setAttribute('aria-busy', 'true');
    error.hidden = true;

    try {
        const response = await fetch(url, { signal: request.signal });
        if (!response.ok) {
            throw new Error(`The source code request returned a ${response.status} status code.`);
        }

        files.innerHTML = await response.text();
        highlightSourceCode(modal);
        enableKeyboardTabNavigation(modal);
        scrollToMarkedLines(modal);
    } catch (exception) {
        if ('AbortError' === exception.name) {
            return;
        }

        console.error(exception);
        error.hidden = false;
    } finally {
        if (sourceCodeRequest === request) {
            files.removeAttribute('aria-busy');
        }
    }
}

function highlightSourceCode(modal) {
    modal.querySelectorAll('code:not([data-highlighted])').forEach((code) => hljs.highlightElement(code));
}

// Bootstrap only listens to the arrow keys in tabs that have been clicked before
// (or that were active when the page loaded), so the instances are created here
function enableKeyboardTabNavigation(modal) {
    modal.querySelectorAll('[role="tab"]').forEach((tab) => window.bootstrap.Tab.getOrCreateInstance(tab));
}

// it's called when the modal is shown and when its code is loaded, because any of them can happen first
function scrollToMarkedLines(container) {
    container.querySelectorAll('pre').forEach((pre) => {
        const firstMark = pre.querySelector('.source-code-mark');
        if (null === firstMark || 0 === pre.clientHeight) {
            return;
        }

        const offset = firstMark.getBoundingClientRect().top - pre.getBoundingClientRect().top;
        pre.scrollTop += offset - pre.clientHeight / 4;
    });
}
