import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static targets = ['timeframe', 'frequency', 'infomessage'];
    static values = { config: Object };

    connect() {
        console.log('Récupération de la config');
        console.log(this.configValue.ACQUISITION_NORMAL);

        console.log('Chargement');
        this.getSelectRadio();
    }

    change() {
        console.log('Changement');
        this.getSelectRadio();
    }

    getSelectRadio() {

        // On récupère la valeur du select
        const selectValue = this.timeframeTarget.value;

        // On cherche l'élément radio coché à l'intérieur du conteneur ciblé par frequencyTarget
        const checkedRadio = this.frequencyTarget.querySelector('input[type="radio"]:checked');

        // On récupère la valeur de l'élément radio.
        const radioValue = checkedRadio.value;

        console.log('Select => ', selectValue);
        console.log('Radio  => ', radioValue);

        let cycle;
        if (selectValue === 'ACQUISITION') {
            if (radioValue === 'QUICK') cycle = this.configValue.ACQUISITION_QUICK;
            if (radioValue === 'NORMAL') cycle = this.configValue.ACQUISITION_NORMAL;
            if (radioValue === 'SLOW') cycle = this.configValue.ACQUISITION_SLOW;
            // Affectation du message
            this.infomessageTarget.textContent = 'Un nombre de révision dégressif tous les ' + cycle + ' Jours';

        }
        if (selectValue === 'MAINTENANCE') {
            if (radioValue === 'QUICK') cycle = this.configValue.MAINTENANCE_QUICK;
            if (radioValue === 'NORMAL') cycle = this.configValue.MAINTENANCE_NORMAL;
            if (radioValue === 'SLOW') cycle = this.configValue.MAINTENANCE_SLOW;
            // Affectation du message
            this.infomessageTarget.textContent = 'Une révision tous les ' + cycle + ' Jours';
        }

        if (selectValue === 'MAINTENANCE') {
            if (radioValue === 'QUICK') {
                cycle = this.configValue.MAINTENANCE_QUICK;
                this.infomessageTarget.textContent = '24 révisions au rythme d\'une révision tous les ' + cycle + ' Jours';
            }
            if (radioValue === 'NORMAL') {
                cycle = this.configValue.MAINTENANCE_NORMAL;
                this.infomessageTarget.textContent = '12 révisions au rythme d\'une révision tous les ' + cycle + ' Jours';
            }
            if (radioValue === 'SLOW') {
                cycle = this.configValue.MAINTENANCE_SLOW;
                this.infomessageTarget.textContent = '6 révisions au rythme d\'une révision tous les ' + cycle + ' Jours';
            }
        }

    }

}