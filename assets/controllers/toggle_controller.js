import { Controller } from '@hotwired/stimulus';

// Ce controleur concerne l'affichage d'une QR au moment de l'exécution d'une Faq
// Lors de cet affichage, tous les noms des règles sont affichés sous la forme d'un lien
// Par défaut, tous les contenus des règles sont cachés 
// Ce controleur gère le click sur un de ces liens.
// Si l'utilisateur clique sur un lien le contenu de la règle change d'état : affiché / pas affiché.
export default class extends Controller {
    static targets = ['element']
    static classes = ['hidden']

    toggle(event) {
        event.preventDefault();

        // console.log(this.elementTarget);
        // console.log(this.hiddenClass);

        // On bascule la classe sur la cible
        this.elementTarget.classList.toggle(this.hiddenClass);
    }
}