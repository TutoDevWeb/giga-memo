import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static targets = ['timeframe', 'frequency'];
    static values = { config: Object, messages: Object };

    connect() {
        console.log('Chargement');

        // console.log('Select => ', this.timeframeTarget.value);

        // // Récupérer et afficher la valeur du radio actuellement coché
        // console.log('frequency');
        // console.log(this.frequencyTarget);

        // // On cherche le radio coché à l'intérieur du conteneur ciblé par frequencyTarget
        // const checkedRadio = this.frequencyTarget.querySelector('input[type="radio"]:checked');
        // console.log('Radio coché au chargement => ', checkedRadio ? checkedRadio.value : 'aucun');

        console.log('Récupération de la config');
        console.log(this.configValue);
        console.log(this.configValue['Acquisition des connaissances']);
        console.log(this.configValue['Acquisition des connaissances'][7]);

        console.log(this.messagesValue);


    }

    change(event) {
        const selectedValue = event.target.value;

        console.log('planning change => ', selectedValue);
    }

}