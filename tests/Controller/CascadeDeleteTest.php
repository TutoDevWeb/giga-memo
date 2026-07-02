<?php

namespace App\Tests\Controller;

use App\Entity\Categories;
use App\Entity\Faqs;
use App\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CascadeDeleteTest extends WebTestCase
{
    public function testCategoryAndFaqAreDeletedInCascade(): void
    {
        // 1. On démarre le client Symfony (le faux navigateur) et on récupère l'EntityManager
        $client = static::createClient();
        $container = static::getContainer();
        $em = $container->get('doctrine')->getManager();

        // 2. On prépare un utilisateur en base de données pour posséder les ressources
        $user = new Users();
        $user->setEmail('test-cascade@example.com');
        $user->setPassword('fake-password-hash');
        // Ajoute ici d'autres champs obligatoires de ton entité Users si nécessaire (ex: $user->setRoles(...))
        $em->persist($user);

        // 3. SCÉNARIO : On crée une catégorie rattachée à cet utilisateur
        $category = new Categories();
        $category->setName('Catégorie Test Cascade');
        $category->setUser($user);
        $em->persist($category);

        // 4. SCÉNARIO : On crée une FAQ rattachée à cette catégorie et à cet utilisateur
        $faq = new Faqs();
        $faq->setName('Nucléaire');
        $faq->setCategory($category);
        $faq->setUser($user);
        $em->persist($faq);

        // On envoie tout ça en base de données pour initialiser notre état
        $em->flush();

        // On sauvegarde les IDs pour vérifier leur disparition après la suppression
        $categoryId = $category->getId();
        $faqId = $faq->getId();

        // On s'assure d'abord que Doctrine les trouve bien en base
        $this->assertNotNull($categoryId);
        $this->assertNotNull($faqId);

        // 5. SCÉNARIO : On simule la connexion de l'utilisateur (nécessaire pour passer ton Voter)
        $client->loginUser($user);

        // 6. SCÉNARIO : On déclenche la suppression de la catégorie. 
        // Ici, on va simuler l'appel directement via l'EntityManager, ou si tu as une route spécifique 
        // (ex: /categories/delete/{id}), on pourrait faire : $client->request('POST', '/categories/delete/' . $categoryId);
        // Pour valider la cascade Doctrine pure, on va la supprimer via l'EntityManager :
        $categoryRepository = $em->getRepository(Categories::class);
        $categoryToDelete = $categoryRepository->find($categoryId);

        $em->remove($categoryToDelete);
        $em->flush(); // C'est ce flush qui va déclencher la cascade SQL !

        // On force Doctrine à vider son cache mémoire pour aller interroger la vraie BDD
        $em->clear();

        // 7. VÉRIFICATIONS : Est-ce que tout a disparu ?
        $deletedCategory = $em->getRepository(Categories::class)->find($categoryId);
        $deletedFaq = $em->getRepository(Faqs::class)->find($faqId);

        // On affirme que la catégorie ET la faq n'existent plus du tout (doivent être null)
        $this->assertNull($deletedCategory, 'La catégorie n\'a pas été supprimée.');
        $this->assertNull($deletedFaq, 'La FAQ est toujours présente, la cascade a échoué !');
    }
}
