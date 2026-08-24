import { Controller } from '@hotwired/stimulus';

// Ce contrôleur remplace l'ancien public/assets/js/scripts.js.
// Il gère les boutons qui déclenchent une action Ajax protégée par un jeton CSRF
// (A Revoir / Ne plus revoir / Restart / Reset Review) et qui reçoivent en retour
// les 4 compteurs à jour (nbRemainingToRun, nbTotalToRun, nbRemainingToReview, nbTotalToReview).
//
// Sur le conteneur commun aux boutons et aux compteurs :
// data-controller="counter-action"
//
// Sur chaque bouton déclencheur :
// data-action="click->counter-action#send"
// data-url="{{ path('...') }}"
// data-token="{{ csrf_token('...') }}"
//
// Sur chaque compteur à mettre à jour (une page n'affiche pas forcément les 4) :
// data-counter-action-target="remainingToRun|totalToRun|remainingToReview|totalToReview"
export default class extends Controller {
    static targets = ['remainingToRun', 'totalToRun', 'remainingToReview', 'totalToReview'];

    async send(event) {
        event.preventDefault();

        const button = event.currentTarget;
        const url = button.dataset.url;
        const token = button.dataset.token;

        let response;

        try {
            response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ _token: token }),
            });
        } catch (error) {
            alert('Ton serveur ne répond pas correctement');
            return;
        }

        if (!response.ok) {
            alert('Ton serveur ne répond pas correctement');
            return;
        }

        const data = await response.json();

        this.updateCounters(this.remainingToRunTargets, data.nbRemainingToRun);
        this.updateCounters(this.totalToRunTargets, data.nbTotalToRun);
        this.updateCounters(this.remainingToReviewTargets, data.nbRemainingToReview);
        this.updateCounters(this.totalToReviewTargets, data.nbTotalToReview);
    }

    updateCounters(targets, value) {
        if (value === undefined) {
            return;
        }

        targets.forEach((target) => {
            target.textContent = value;
        });
    }
}
