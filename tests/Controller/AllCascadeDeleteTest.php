<?php

namespace App\Tests\Controller;

use App\Entity\Categories;
use App\Entity\Couples;
use App\Entity\Faqs;
use App\Entity\Rules;
use App\Entity\Users;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AllCascadeDeleteTest extends WebTestCase
{
    public function testCreateAndDeleteCategoryWithFaq(): void
    {
        // 1. Démarrage du noyau Symfony
        self::createClient();
        $container = self::getContainer();
        $em = $container->get('doctrine')->getManager();

        // 2. Création de l'utilisateur
        $user = new Users();
        $user->setEmail('all-cascade-faq-test@example.com');
        $user->setPassword('password123');
        $em->persist($user);

        // 3. Création de la catégorie
        $category = new Categories();
        $category->setName('Energie Nucléaire');
        $category->setUser($user);
        $em->persist($category);

        // 4. Création de la FAQ reliée
        $faq = new Faqs();
        $faq->setName('Techno');
        $faq->setUser($user);

        // On lie dans les deux sens pour que Doctrine ait l'objet complet en mémoire
        $faq->setCategory($category);
        $category->addFaq($faq);

        $em->persist($faq);

        // 5. Création d'une règles
        $rule = new Rules();
        $rule->setName('Une règle');
        $rule->setContent('Ma règle fonctionne comme ceci');
        $rule->setUser($user);

        // On lie la règle à la Faq
        $rule->setFaq($faq);
        $faq->addRule($rule);

        $em->persist($rule);

        // 6. Création d'un couple de question / réponse
        $couple = new Couples();
        $couple->setNum(1);
        $couple->setQuestion('Une première question');
        $couple->setReponse('Une première reponse');
        $couple->setTodoRun(true);
        $couple->setTodoReview(true);
        $couple->setSelectReview(true);

        $couple->setUser($user);

        // On lie le couple à la Faq
        $couple->setFaq($faq);
        $faq->addCouple($couple);

        $em->persist($couple);

        // On envoie le tout en base
        $em->flush();

        $categoryId = $category->getId();
        $faqId = $faq->getId();
        $ruleId = $rule->getId();
        $coupleId = $couple->getId();

        // On s'assure que tous les id ont bien été créés
        $this->assertNotNull($categoryId);
        $this->assertNotNull($faqId);
        $this->assertNotNull($ruleId);
        $this->assertNotNull($coupleId);

        // 5. Suppression de la catégorie
        $em->remove($category);
        $em->flush();

        // On vide le cache de l'EntityManager pour forcer une vraie vérification en BDD
        $em->clear();

        // 6. Vérifications finales
        $deletedCategory = $em->getRepository(Categories::class)->find($categoryId);
        $deletedFaq = $em->getRepository(Faqs::class)->find($faqId);
        $deletedRule = $em->getRepository(Rules::class)->find($ruleId);
        $deletedCouple = $em->getRepository(Couples::class)->find($coupleId);

        // On affirme que la catégorie ET tout ce qui dépend d'elle a été supprimé
        $this->assertNull($deletedCategory, 'La catégorie n\'a pas été supprimée.');
        $this->assertNull($deletedFaq, 'La FAQ est toujours là, orphanRemoval n\'a pas fonctionné.');
        $this->assertNull($deletedRule, 'La Rule est toujours là, orphanRemoval n\'a pas fonctionné.');
        $this->assertNull($deletedCouple, 'Le Couple est toujours là, orphanRemoval n\'a pas fonctionné.');
    }
}
