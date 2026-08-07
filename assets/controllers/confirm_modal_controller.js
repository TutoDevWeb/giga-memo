import { Controller } from '@hotwired/stimulus';

// Ce controleur intervient dans l'affichage d'une modale bootstrap utilisée pour confirmer les actions supprimer
// Les boutons qui déclenchent la modale sont dans les fichiers _delete_form.twig.html de chaque CRUD
// La modale est dans le fichier _partials/_delete_modal.twig.html
// La modale est une modale boostrap. 
// C'est bootstrap qui déclenche l'affichage de la modale lorsque l'utilisateur appui sur un bouton.
// Il faut donc implanter sur le bouton les info nécessaires à l'action de suppression depuis la modale.
// A cette fin, sur le bouton sont implantés le 
// La route du controleur de suppression data-action="{{ path('app_couples_delete', {'id_faq': faq.id, 'id_couple': couple.id}) }}" 
// et le token 
// data-token="{{ csrf_token('delete' ~ couple.id) }}"
//
// Ensuite action et token sont passer au formulaire contenu dans la modale
// Dans la modale le bouton a été remplacer par un form.
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

        // Récupération des données passées sur le bouton
        const action = button.dataset.action;
        const token = button.dataset.token;

        console.log('action => ', action);
        console.log('token => ', token);

        // On passe ici action et token à la modale
        if (action && this.hasFormTarget) {
            this.formTarget.action = action;
        }

        if (token && this.hasTokenTarget) {
            this.tokenTarget.value = token;
        }
    }
}