import { Controller } from '@hotwired/stimulus';

// Ce controleur intervient dans l'affichage d'une modale bootstrap utilisée pour confirmer les actions supprimer
// Ce controleur récupère l'action(url) et le token depuis le bouton qui ouvre la modale.
// Ensuite il cible le formulaire qui contient le bouton de la modale qui va faire la suppression.
// Il lui passe l'action et le token.
// Les boutons qui déclenchent la modale sont dans les fichiers _delete_form.twig.html de chaque CRUD
// La modale est dans le fichier _partials/_delete_modal.twig.html
// La modale étant une modale boostrap. 
// C'est bootstrap qui déclenche l'affichage de la modale lorsque l'utilisateur appui sur un bouton.

// <form method="post" action="" data-confirm-modal-target="form">
// 	<input type="hidden" name="_token" value="" data-confirm-modal-target="token">
// 	<button type="submit" class="btn btn-delete-confirm">Supprimer définitivement</button>
// </form>

export default class extends Controller {
    static targets = ['form', 'token'];

    // S'exécute automatiquement quand la modale Bootstrap déclenche l'événement "show.bs.modal"
    open(event) {
        // Le bouton qui a ouvert la modale
        const button = event.relatedTarget;

        if (!button) return;

        // Récupération des données présentes sur le bouton qui ouvre la modale
        const action = button.dataset.action;
        const token = button.dataset.token;

        console.log('action => ', action);
        console.log('token => ', token);

        // On passe action et token à la modale
        if (action && this.hasFormTarget) {
            this.formTarget.action = action;
        }

        if (token && this.hasTokenTarget) {
            this.tokenTarget.value = token;
        }
    }
}