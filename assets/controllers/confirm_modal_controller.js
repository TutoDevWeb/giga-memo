import { Controller } from '@hotwired/stimulus';

// Ce controleur intervient dans l'affichage d'une boite modale bootstrap utilisée pour confirmer les actions supprimer
// Les boutons qui déclenchent la boite modale sont dans les fichiers _delete_form.twig.html de chaque CRUD
// La boite modale est dans le fichier _partials/_delete_modal.twig.html
// Sur le bouton sont implantés le 
// data-action="{{ path('app_couples_delete', {'id_faq': faq.id, 'id_couple': couple.id}) }}" 
// et le 
// data-token="{{ csrf_token('delete' ~ couple.id) }}"
//
// Ensuite action et token sont passer au formulaire contenu dans la modale
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

        // Mise à jour du formulaire de la modale
        if (action && this.hasFormTarget) {
            this.formTarget.action = action;
        }

        if (token && this.hasTokenTarget) {
            this.tokenTarget.value = token;
        }
    }
}