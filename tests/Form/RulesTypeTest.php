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
 * "content". Le champ "faq" est un EntityType requis (pas de query_builder,
 * pas de contrainte d'appartenance au niveau du formulaire : c'est le
 * contrôleur qui réassigne la faq après soumission).
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

    private function submit(array $data): FormInterface
    {
        // RulesController assigne toujours l'utilisateur connecté avant de créer le
        // formulaire (le champ n'existe pas dans RulesType) : on reproduit ce contexte
        // pour que la validation de l'entité (HasUserTrait) ne fausse pas ces tests.
        $rule = new Rules();
        $rule->setUser($this->user);

        $form = $this->formFactory->create(RulesType::class, $rule, ['csrf_protection' => false]);
        $form->submit($data);

        return $form;
    }

    public function testValidDataIsAccepted(): void
    {
        $form = $this->submit([
            'faq' => (string) $this->faq->getId(),
            'name' => 'Ma regle',
            'content' => 'Enonce de la regle',
        ]);

        $this->assertTrue($form->isValid());
        $rule = $form->getData();
        $this->assertSame($this->faq->getId(), $rule->getFaq()->getId());
        $this->assertSame('Ma regle', $rule->getName());
        $this->assertSame('Enonce de la regle', $rule->getContent());
    }

    public function testMissingFaqIsAcceptedByTheFormDespiteTheNotNullDbColumn(): void
    {
        // Rules::$faq n'a pas de contrainte Assert\NotNull/NotBlank (contrairement à
        // Faqs::$category) : le formulaire seul ne rejette donc pas une faq absente,
        // même si la colonne SQL est NOT NULL. En pratique, RulesController réassigne
        // toujours la faq avant persistance et le champ est désactivé à l'affichage.
        $form = $this->submit([
            'name' => 'Ma regle',
            'content' => 'Enonce de la regle',
        ]);

        $this->assertTrue($form->isValid());
        $this->assertNull($form->getData()->getFaq());
    }

    public function testUnknownFaqIdIsRejected(): void
    {
        $form = $this->submit([
            'faq' => '999999999',
            'name' => 'Ma regle',
            'content' => 'Enonce de la regle',
        ]);

        $this->assertFalse($form->isValid());
        $this->assertFalse($form->get('faq')->isValid());
    }
}
