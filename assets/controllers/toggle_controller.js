import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['element']
    static classes = ['hidden']

    toggle(event) {
        event.preventDefault();

        console.log(this.elementTarget);

        // On bascule la classe sur la cible
        this.elementTarget.classList.toggle(this.hiddenClass);
    }
}