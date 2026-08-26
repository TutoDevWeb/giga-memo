<?php

namespace App\Tests\Form;

use App\Entity\Categories;
use App\Entity\Faqs;
use App\Entity\Rules;
use App\Entity\Users;
use App\Form\RulesType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Ces tests couvrent RulesType : mapping des champs "faq", "name" et
 * "content". Le champ "faq" est un EntityType désactivé ('disabled' => true) :
 * sa valeur ne peut jamais venir des données soumises, seulement de l'entité
 * telle qu'assignée avant la création du formulaire (c'est ce que fait
 * RulesController en assignant la faq avant d'appeler createForm()).
 */
class RulesTypeTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private FormFactoryInterface $formFactory;
    private Users $user;
    private Categories $category;
    private Faqs $faq;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);

        $this->user = new Users();
        $this->user->setEmail('rules-type-test-'.uniqid().'@example.com');
        $this->user->setPassword('not-used');
        $this->em->persist($this->user);

        $this->category = new Categories();
        $this->category->setName('Rules Type Test Category');
        $this->category->setUser($this->user);
        $this->em->persist($this->category);

        $this->faq = new Faqs();
        $this->faq->setName('Rules Type Test Faq');
        $this->faq->setCategory($this->category);
        $this->faq->setUser($this->user);
        $this->em->persist($this->faq);

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $categoryId = $this->category->getId();
        $userId = $this->user->getId();
        $this->em->clear();

        $category = $this->em->find(Categories::class, $categoryId);
        if (null !== $category) {
            $this->em->remove($category);
            $this->em->flush();
        }

        $user = $this->em->find(Users::class, $userId);
        if (null !== $user) {
            $this->em->remove($user);
            $this->em->flush();
        }

        parent::tearDown();
    }

    private function submit(array $data, ?Faqs $faq = null): FormInterface
    {
        // RulesController assigne toujours la faq et l'utilisateur connecté avant de
        // créer le formulaire : on reproduit ce contexte (le champ "faq" étant
        // désactivé, aucune valeur soumise ne peut de toute façon l'atteindre).
        $rule = new Rules();
        $rule->setFaq($faq ?? $this->faq);
        $rule->setUser($this->user);

        $form = $this->formFactory->create(RulesType::class, $rule, ['csrf_protection' => false]);
        $form->submit($data);

        return $form;
    }

    public function testValidDataIsAccepted(): void
    {
        $form = $this->submit([
            'name' => 'Ma regle',
            'content' => 'Enonce de la regle',
        ]);

        $this->assertTrue($form->isValid());
        $rule = $form->getData();
        $this->assertSame($this->faq->getId(), $rule->getFaq()->getId());
        $this->assertSame('Ma regle', $rule->getName());
        $this->assertSame('Enonce de la regle', $rule->getContent());
    }

    public function testFaqFieldIsDisabledAndIgnoresSubmittedValue(): void
    {
        // Le champ "faq" est désactivé (RulesType::buildForm) : la faq utilisée est
        // celle assignée à l'entité avant la création du formulaire, jamais celle
        // soumise dans les données - même si elle correspond à une faq existante.
        $otherFaq = new Faqs();
        $otherFaq->setName('Autre Faq');
        $otherFaq->setCategory($this->category);
        $otherFaq->setUser($this->user);
        $this->em->persist($otherFaq);
        $this->em->flush();

        $form = $this->submit([
            'faq' => (string) $otherFaq->getId(),
            'name' => 'Ma regle',
            'content' => 'Enonce de la regle',
        ]);

        $this->assertTrue($form->isValid());
        $this->assertSame($this->faq->getId(), $form->getData()->getFaq()->getId());
    }
}
