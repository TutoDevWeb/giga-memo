import { Controller } from '@hotwired/stimulus';

// Ce controleur concerne l'affichage d'une QR au moment de l'exécution d'une Faq
// Lors de cet affichage, par défaut la question est affichée mais pas la réponse.
// Un click sur le bouton "Voir la réponse" permet d'afficher la réponse.
// Ce controlleur gère ce click.
// La réponse contient une entrée input text pour le texte de la réponse
// et peut contenir des images. Il faut aussi afficher les images. 

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    static targets = ["input", "image"]


    show(event) {
        event.preventDefault();

        console.log(this.inputTarget);

        this.imageTargets.forEach(element => {
            console.log(element);
        });

        // On retire la classe sur l'input
        if (this.hasInputTarget) {
            this.inputTarget.classList.remove('hidden');
        }

        this.imageTargets.forEach(element => {
            element.classList.remove('hidden');
        });
    }
}