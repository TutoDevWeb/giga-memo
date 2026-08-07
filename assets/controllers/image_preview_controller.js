import { Controller } from '@hotwired/stimulus';

// Ce controleur intervient dans l'affichage des listes de QRs au niveau de la route : app_couples_list_by_faq 
// Pour des raisons de place sur l'écran, les photos de la QR ne sont pas affichées directement.
// A la place, un lien est affiché. Au survol de ce lien la photo apparait en superposition, un peu comme une modale.
// Le controleur preview est implanté au niveau du body dans base.html.twig
// <body class="d-flex flex-column min-vh-100" data-controller="answer image-preview">
//
// L'action stimulus est implanté sur le lien
// <a href="#" class="preview-link" 
//             data-image-preview-url="{{ asset(image.name,'images_directory') }}" 
//             data-action="mouseenter->image-preview#show mouseleave->image-preview#hide">
// 
// Les cibles cad l'image et l'overlay
// 	<div class="image-preview-overlay" data-image-preview-target="container">
//    <img src="" alt="Aperçu" data-image-preview-target="image">
//  </div>
// Par défaut l'overlay est en display none grace à la classe class="image-preview-overlay" 
//
// Dans ce controleur, on récupère l'Url de l'image en passant par le mécanisme natif dataset.
// au niveau du lien via l'attribut data-image-preview-url


/* stimulusFetch: 'lazy' */
export default class extends Controller {

    // La cible est le conteneur global de l'aperçu, et l'image à l'intérieur
    static targets = ["container", "image"]

    connect() {
        // On vérifie si la target existe avant de changer son style
        if (this.hasContainerTarget) {
            // Optionnel : s'assurer que le conteneur est caché au chargement
            this.containerTarget.style.display = 'none';
        }
    }

    // Méthode appelée quand la souris entre sur un lien
    show(event) {

        // Si la target n'existe pas sur cette page, on arrête la fonction
        if (!this.hasContainerTarget) return;

        // 1. Récupérer l'URL de l'image stockée dans le data-attribute du lien
        // data-image-preview-url="{{ asset(image.name,'images_directory') }}"
        const imageUrl = event.currentTarget.dataset.imagePreviewUrl;

        console.log('show => ', imageUrl);

        if (!imageUrl) return;

        // 2. Mettre à jour la source de l'image cible
        this.imageTarget.src = imageUrl;

        // 3. Afficher le conteneur
        // On utilise 'flex' pour le centrage CSS défini après
        this.containerTarget.style.display = 'flex';
    }

    // Méthode appelée quand la souris quitte le lien
    hide() {

        if (this.hasContainerTarget) {
            // Masquer le conteneur
            this.containerTarget.style.display = 'none';
        }

        // Optionnel : vider la source pour éviter un "flash" de l'ancienne image au prochain survol
        this.imageTarget.src = '';
    }
}