import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ["faqSelect"]

    async updateFaqs(event) {
        const categoryId = event.target.value;

        if (!categoryId) {
            this.faqSelectTarget.innerHTML = '<option value="">Choisissez une catégorie d\'abord</option>';
            return;
        }

        // Appel à ton endpoint Symfony
        const response = await fetch(`/faq/list-by-category/${categoryId}`);
        const html = await response.text();

        // Mise à jour du second select
        this.faqSelectTarget.innerHTML = html;
    }
}