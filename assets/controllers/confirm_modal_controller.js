import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['form', 'token'];

    // S'exécute automatiquement quand la modale Bootstrap déclenche l'événement "show.bs.modal"
    open(event) {
        // Le bouton qui a ouvert la modale
        const button = event.relatedTarget;

        if (!button) return;

        // Récupération des données passées sur le bouton
        const action = button.dataset.action;
        const token = button.dataset.token;

        // Mise à jour du formulaire de la modale
        if (action && this.hasFormTarget) {
            this.formTarget.action = action;
        }

        if (token && this.hasTokenTarget) {
            this.tokenTarget.value = token;
        }
    }
}