import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ['input']


    show(event) {
        event.preventDefault();

        console.log(this.inputTarget);

        // On retire la classe
        this.inputTarget.classList.remove('reponse-hide');
    }
}