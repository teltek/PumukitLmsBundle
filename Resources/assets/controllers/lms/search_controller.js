import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['modal', 'preview'];

    connect() {
        const links = this.element.querySelectorAll('.search-form a');
        links[links.length - 1]?.setAttribute('hidden', '');
        this.onClick = this.onClick.bind(this);
        this.element.addEventListener('click', this.onClick);
        this.modalTarget.addEventListener('hidden.bs.modal', () => this.previewTarget.replaceChildren());
    }

    disconnect() {
        this.element.removeEventListener('click', this.onClick);
    }

    onClick(event) {
        const link = event.target.closest('a.multimediaobject');
        if (!link) {
            return;
        }
        event.preventDefault();

        const isMedia = ['image', 'document', 'external'].some((type) => link.classList.contains(type));
        this.url = link.href.replace('/video/', isMedia ? '/iframe/player/' : '/iframe/');

        const iframe = document.createElement('iframe');
        iframe.src = this.url;
        iframe.title = link.title;
        iframe.allowFullscreen = true;
        const ratio = document.createElement('div');
        ratio.className = 'ratio ratio-16x9';
        ratio.append(iframe);
        this.previewTarget.replaceChildren(ratio);

        window.bootstrap.Modal.getOrCreateInstance(this.modalTarget).show();
    }

    publish() {
        if (window.parent && this.url) {
            window.parent.postMessage({ url: this.url }, '*');
        }
        window.bootstrap.Modal.getOrCreateInstance(this.modalTarget).hide();
    }
}
