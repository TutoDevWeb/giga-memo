import { Controller } from '@hotwired/stimulus';

// Ce controleur est utilisé dans le cadre de la gestion des deux select couplés catégorie et Faq.
// Chaque catégorie contient un certain nombre de faq.
// Un changement sur le select catégorie entraine la mise à jour du select Faq.
// Dans le form type SelectFaqFormType.php 
// L'action stimulus est placée sur le premier select
// 'attr' => ['data-action' => 'change->dynamic-select#updateFaqs']
// La cible stimulus est placée sur le deuxième
// 'attr' => ['data-dynamic-select-target' => 'faqSelect'], // Cible Stimulus 
//
export default class extends Controller {
    static targets = ["faqSelect"]

    async updateFaqs(event) {
        const categoryId = event.target.value;

        if (!categoryId) {
            this.faqSelectTarget.innerHTML = '<option value="">Choisissez une catégorie d\'abord</option>';
            return;
        }

        // Appel à un controleur Symfony qui renvoie le HTML du select qui contient la liste des Faq
        const response = await fetch(`/faq/list-by-category/${categoryId}`);
        const html = await response.text();

        // Mise à jour du second select des Faqs.
        this.faqSelectTarget.innerHTML = html;
    }
}