import { Controller } from '@hotwired/stimulus';

export default class extends Controller {

    static targets = ['timeframe', 'frequency', 'infomessage'];

    // La conf est centralisée dans la classe Config.php
    static values = { config: Object };

    connect() {
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

        let cycle_duration;
        let cycle_count;
        if (selectValue === 'ACQUISITION') {
            if (radioValue === 'QUICK') cycle_duration = this.configValue.ACQUISITION_CYCLE_DURATION_QUICK;
            if (radioValue === 'NORMAL') cycle_duration = this.configValue.ACQUISITION_CYCLE_DURATION_NORMAL;
            if (radioValue === 'SLOW') cycle_duration = this.configValue.ACQUISITION_CYCLE_DURATION_SLOW;

            cycle_count = this.configValue.ACQUISITION_CYCLE_COUNT;
            const nb_day_total = cycle_duration * cycle_count;
            const nb_revision_days = this.configValue.ACQUISITION_NB_REVISION_DAY;

            // Affectation du message
            const message = `
                    <p>Des dates de révision en nombre dégressif avec des cycles de ${cycle_duration} Jours.</p>
                    <ul>
                        <li>Un cycle de 5 dates.</li>
                        <li>... puis deux cycles de 3 dates.</li>
                        <li>... puis quatre cycles de 2 dates.</li>
                        <li>... puis cinq cycles de 1 date.</li>
                    </ul>
                    <br>
                    <p>${nb_revision_days} dates de révision réparties sur ${cycle_count} cycles.</p>
                    <p>Durée totale de ${nb_day_total} jours.</p>
                    `;

            this.infomessageTarget.innerHTML = message;
        }

        if (selectValue === 'MAINTENANCE') {
            if (radioValue === 'QUICK') {
                cycle_duration = this.configValue.MAINTENANCE_CYCLE_DURATION_QUICK;
                cycle_count = this.configValue.MAINTENANCE_CYCLE_COUNT_QUICK;
                this.infomessageTarget.innerHTML = `<p>${cycle_count} dates de révision au rythme d'une révision tous les ${cycle_duration} jours.</p>`;
            }
            if (radioValue === 'NORMAL') {
                cycle_duration = this.configValue.MAINTENANCE_CYCLE_DURATION_NORMAL;
                cycle_count = this.configValue.MAINTENANCE_CYCLE_COUNT_NORMAL;
                this.infomessageTarget.innerHTML = `<p>${cycle_count} dates de révision au rythme d'une révision tous les ${cycle_duration} jours.</p>`;
            }
            if (radioValue === 'SLOW') {
                cycle_duration = this.configValue.MAINTENANCE_CYCLE_DURATION_SLOW;
                cycle_count = this.configValue.MAINTENANCE_CYCLE_COUNT_SLOW;
                this.infomessageTarget.innerHTML = `<p>${cycle_count} dates de révision au rythme d'une révision tous les ${cycle_duration} jours.</p>`;
            }
            this.infomessageTarget.innerHTML += '<p>Durée totale d\'environ 360 jours.</p>';
        }

    }

}