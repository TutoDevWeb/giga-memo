<?php

namespace App\Tests\Form;

use App\Entity\Categories;
use App\Entity\Couples;
use App\Entity\Faqs;
use App\Entity\Rules;
use App\Entity\Users;
use App\Form\CoupleFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;

/**
 * Ces tests couvrent le mapping des champs "num", "rules", "question",
 * "reponse", "faq" et "from" de CoupleFormType. Les contraintes du champ
 * "images" (format, taille) sont couvertes séparément dans
 * CoupleFormTypeImagesConstraintsTest.
 */
class CoupleFormTypeTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private FormFactoryInterface $formFactory;
    private Users $user;
    private Categories $category;
    private Faqs $faq;
    private Faqs $otherFaq;
    private Rules $rule;
    private Rules $otherFaqRule;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);

        $this->user = new Users();
        $this->user->setEmail('couple-form-test-'.uniqid().'@example.com');
        $this->user->setPassword('not-used');
        $this->em->persist($this->user);

        $this->category = new Categories();
        $this->category->setName('Couple Form Test Category');
        $this->category->setUser($this->user);
        $this->em->persist($this->category);

        $this->faq = new Faqs();
        $this->faq->setName('Couple Form Test Faq');
        $this->faq->setCategory($this->category);
        $this->faq->setUser($this->user);
        $this->em->persist($this->faq);

        $this->otherFaq = new Faqs();
        $this->otherFaq->setName('Couple Form Other Faq');
        $this->otherFaq->setCategory($this->category);
        $this->otherFaq->setUser($this->user);
        $this->em->persist($this->otherFaq);

        $this->rule = new Rules();
        $this->rule->setName('Regle de la faq');
        $this->rule->setContent('Contenu');
        $this->rule->setFaq($this->faq);
        $this->rule->setUser($this->user);
        $this->em->persist($this->rule);

        $this->otherFaqRule = new Rules();
        $this->otherFaqRule->setName('Regle de l\'autre faq');
        $this->otherFaqRule->setContent('Contenu');
        $this->otherFaqRule->setFaq($this->otherFaq);
        $this->otherFaqRule->setUser($this->user);
        $this->em->persist($this->otherFaqRule);

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
        // CouplesController assigne toujours la faq avant de créer le formulaire : on
        // reproduit ce contexte (le champ "faq" étant désactivé, aucune valeur soumise
        // ne peut de toute façon l'atteindre).
        $couple = new Couples();
        $couple->setFaq($faq ?? $this->faq);
        $couple->setUser($this->user);

        $form = $this->formFactory->create(CoupleFormType::class, $couple, [
            'faq' => $faq ?? $this->faq,
            'csrf_protection' => false,
        ]);
        $form->submit($data);

        return $form;
    }

    public function testFaqOptionIsRequired(): void
    {
        $this->expectException(MissingOptionsException::class);

        $this->formFactory->create(CoupleFormType::class, new Couples());
    }

    public function testValidDataIsAccepted(): void
    {
        $form = $this->submit([
            'num' => '1',
            'rules' => [(string) $this->rule->getId()],
            'question' => 'Une question',
            'reponse' => 'Une reponse',
            'faq' => (string) $this->faq->getId(),
            'from' => 'run',
        ]);

        $this->assertTrue($form->isValid());
        $couple = $form->getData();
        $this->assertSame(1, $couple->getNum());
        $this->assertSame('Une question', $couple->getQuestion());
        $this->assertSame('Une reponse', $couple->getReponse());
        $this->assertSame($this->faq->getId(), $couple->getFaq()->getId());
        $this->assertCount(1, $couple->getRules());
        $this->assertSame($this->rule->getId(), $couple->getRules()->first()->getId());
    }

    public function testReponseIsOptional(): void
    {
        $form = $this->submit([
            'num' => '1',
            'rules' => [],
            'question' => 'Une question',
            'reponse' => '',
            'faq' => (string) $this->faq->getId(),
            'from' => 'run',
        ]);

        $this->assertTrue($form->isValid());
    }

    public function testEmptyQuestionIsAcceptedDespiteBeingMarkedRequired(): void
    {
        // Couples::$question n'a aucune contrainte Assert : l'option 'required' (par
        // défaut) du champ TextType n'affecte que le rendu HTML (attribut "required"),
        // pas la validation. Une chaîne vide est donc acceptée par le formulaire seul.
        $form = $this->submit([
            'num' => '1',
            'rules' => [],
            'question' => '',
            'reponse' => '',
            'faq' => (string) $this->faq->getId(),
            'from' => 'run',
        ]);

        $this->assertTrue($form->isValid());
        $this->assertNull($form->getData()->getQuestion());
    }

    public function testRuleFromAnotherFaqIsRejected(): void
    {
        // Le query_builder du champ "rules" ne propose que les règles de la
        // faq passée en option : une règle d'une autre faq n'est pas un choix valide.
        $form = $this->submit([
            'num' => '1',
            'rules' => [(string) $this->otherFaqRule->getId()],
            'question' => 'Une question',
            'reponse' => '',
            'faq' => (string) $this->faq->getId(),
            'from' => 'run',
        ]);

        $this->assertFalse($form->isValid());
        $this->assertFalse($form->get('rules')->isValid());
    }

    public function testFromFieldIsNotMappedToTheEntity(): void
    {
        // "from" a 'mapped' => false : il ne doit avoir aucun effet sur l'entité,
        // c'est juste une information transmise pour la redirection du contrôleur.
        $form = $this->submit([
            'num' => '1',
            'rules' => [],
            'question' => 'Une question',
            'reponse' => '',
            'faq' => (string) $this->faq->getId(),
            'from' => 'review',
        ]);

        $this->assertTrue($form->isValid());
        $this->assertSame('review', $form->get('from')->getData());
    }
}
