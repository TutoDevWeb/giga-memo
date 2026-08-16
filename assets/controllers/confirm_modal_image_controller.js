import { Controller } from '@hotwired/stimulus';

// Ce controleur intervient dans l'affichage d'une modale bootstrap utilisée pour confirmer la suppression des photos.
// 
export default class extends Controller {

    // S'exécute automatiquement quand la modale Bootstrap déclenche l'événement "show.bs.modal"
    // donc quand la modale s'ouvre
    open(event) {

        // Le lien qui a ouvert la modale
        const link = event.relatedTarget;

        // On a besoin de garder l'adresse du div dans lequel on affiche l'image et le bouton supprimer
        // On garde cette addresse dans la classe sous forme de propriétés.
        this.elementToRemove = link.closest('div');

        if (!link) return;

        // Récupération des données présentes sur le lien supprimer qui ouvre la modale
        this.elementAction = link.getAttribute('href');
        this.elementToken = link.dataset.token;

        console.log('elementAction => ', this.elementAction);
        console.log('elementToken  => ', this.elementToken);
        console.log('elementToRemove => ', this.elementToRemove);

    }

    // Lorque l'on clique sur le bouton "supprimer définitivement" de la modale
    delete(event) {

        console.log('delete');

        // On envoie la requête ajax avec les éléments que l'on a récupéré dans le open
        // this.elementAction
        // this.elementToken
        fetch(

            this.elementAction, {
            method: "DELETE",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ "_token": this.elementToken })

        }).then(response => response.json())
            .then(data => {
                console.log(data);
            })

        // On supprime le div qui contient l'image et son lien supprimer.    
        if (this.elementToRemove)
            this.elementToRemove.remove();

    }
}