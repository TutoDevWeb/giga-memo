import { Controller } from '@hotwired/stimulus';


export default class extends Controller {

    // S'exécute automatiquement quand la modale Bootstrap déclenche l'événement "show.bs.modal"
    open(event) {

        // Le lien qui a ouvert la modale
        const link = event.relatedTarget;

        // On garde les élements pour supprimer l'image dans la classe sous forme de propriétés.
        // le div parent
        this.elementToRemove = link.closest('div');

        if (!link) return;

        // Récupération des données présentes sur le bouton qui ouvre la modale
        this.elementAction = link.getAttribute('href');
        this.elementToken = link.dataset.token;

        console.log('elementAction => ', this.elementAction);
        console.log('elementToken  => ', this.elementToken);
        console.log('elementToRemove => ', this.elementToRemove);

    }

    delete(event) {

        //event.preventDefault();
        console.log('delete');

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

        if (this.elementToRemove)
            this.elementToRemove.remove();

    }
}