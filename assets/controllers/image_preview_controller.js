import { Controller } from '@hotwired/stimulus';

/* stimulusFetch: 'lazy' */
export default class extends Controller {
    // La cible est le conteneur global de l'aperçu, et l'image à l'intérieur
    static targets = ["container", "image"]

    connect() {
        // Optionnel : s'assurer que le conteneur est caché au chargement
        this.containerTarget.style.display = 'none';
    }

    // Méthode appelée quand la souris entre sur un lien
    show(event) {
        // 1. Récupérer l'URL de l'image stockée dans le data-attribute du lien
        const imageUrl = event.currentTarget.dataset.imagePreviewUrl;

        if (!imageUrl) return;

        // 2. Mettre à jour la source de l'image cible
        this.imageTarget.src = imageUrl;

        // 3. Afficher le conteneur
        // On utilise 'flex' pour le centrage CSS défini après
        this.containerTarget.style.display = 'flex';
    }

    // Méthode appelée quand la souris quitte le lien
    hide() {
        // Masquer le conteneur
        this.containerTarget.style.display = 'none';

        // Optionnel : vider la source pour éviter un "flash" de l'ancienne image au prochain survol
        this.imageTarget.src = '';
    }
}