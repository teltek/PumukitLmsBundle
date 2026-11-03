import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static values = { delay: { type: Number, default: 5000 } };

    connect() {
        this.timer = window.setTimeout(() => window.location.reload(), this.delayValue);
    }

    disconnect() {
        window.clearTimeout(this.timer);
    }
}
