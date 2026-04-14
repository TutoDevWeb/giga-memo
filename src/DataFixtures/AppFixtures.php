<?php

namespace App\DataFixtures;

use App\Entity\Couples;
use App\Entity\Faqs;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faq = new Faqs();
        $faq->setName('PHP');
        $manager->persist($faq);
        $manager->flush();

        $couple = new Couples();
        $couple->setQuestion('Comment connaître la version du PHP qui est installée sur ma machine  ?')
            ->setReponse('php -v')
            ->setNum(1)
            ->setFaq($faq);

        $manager->persist($couple);
        $manager->flush();

        $couple = new Couples();
        $couple->setQuestion('Comment lancer le serveur interne de PHP ?')
            ->setReponse('php -S localhost:3000')
            ->setNum(2)
            ->setFaq($faq);

        $manager->persist($couple);
        $manager->flush();

        $couple = new Couples();
        $couple->setQuestion('Comment faire un phpinfo() en ligne de commande ?')
            ->setReponse('php -i')
            ->setNum(3)
            ->setFaq($faq);

        $manager->persist($couple);
        $manager->flush();

        $couple = new Couples();
        $couple->setQuestion('Comment connaître la version de Composer ?')
            ->setReponse('composer -V')
            ->setNum(4)
            ->setFaq($faq);

        $manager->persist($couple);
        $manager->flush();

        $faq = new Faqs();
        $faq->setName('GIT');
        $manager->persist($faq);
        $manager->flush();

        $couple = new Couples();
        $couple->setQuestion('Comment commiter avec un commentaire en option sur une ligne ?')
            ->setReponse('git commit -m "le commentaire"')
            ->setNum(1)
            ->setFaq($faq);

        $manager->persist($couple);
        $manager->flush();

        $couple = new Couples();
        $couple->setQuestion('Comment refaire le dernier commit ?')
            ->setReponse('git commit --amend -m "le commentaire"')
            ->setNum(2)
            ->setFaq($faq);

        $manager->persist($couple);
        $manager->flush();

        $couple = new Couples();
        $couple->setQuestion('Comment annuler le dernier commit ?')
            ->setReponse('git reset --hard HEAD~')
            ->setNum(3)
            ->setFaq($faq);

        $manager->persist($couple);
        $manager->flush();

        $couple = new Couples();
        $couple->setQuestion('Comment avoir les log sous forme courte ?')
            ->setReponse('git log --oneline')
            ->setNum(4)
            ->setFaq($faq);

        $manager->persist($couple);
        $manager->flush();
    }
}
