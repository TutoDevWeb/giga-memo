import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ["input", "image"]


    show(event) {
        event.preventDefault();

        console.log(this.inputTarget);

        this.imageTargets.forEach(element => {
            console.log(element);
        });

        // On retire la classe
        if (this.hasInputTarget) {
            this.inputTarget.classList.remove('hidden');
        }

        this.imageTargets.forEach(element => {
            element.classList.remove('hidden');
        });
    }
}