<?php

namespace App\Tests\Form;

use App\Entity\Categories;
use App\Entity\Faqs;
use App\Entity\Users;
use App\Form\SelectFaqFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\SubmitButton;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * Ces tests couvrent SelectFaqFormType : option "user" obligatoire, le champ
 * "faq" reconstruit dynamiquement selon la catégorie soumise (PRE_SUBMIT), et
 * la pré-sélection catégorie/faq lorsqu'une faq est passée en option
 * (PRE_SET_DATA, utilisé par MainController::index).
 */
class SelectFaqFormTypeTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private FormFactoryInterface $formFactory;
    private Users $user;
    private Users $otherUser;
    private Categories $category;
    private Categories $otherUserCategory;
    private Faqs $faq;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);

        $this->user = new Users();
        $this->user->setEmail('select-faq-form-test-'.uniqid().'@example.com');
        $this->user->setPassword('not-used');
        $this->em->persist($this->user);

        $this->otherUser = new Users();
        $this->otherUser->setEmail('select-faq-form-other-'.uniqid().'@example.com');
        $this->otherUser->setPassword('not-used');
        $this->em->persist($this->otherUser);

        $this->category = new Categories();
        $this->category->setName('Select Faq Form Test Category');
        $this->category->setUser($this->user);
        $this->em->persist($this->category);

        $this->otherUserCategory = new Categories();
        $this->otherUserCategory->setName('Select Faq Form Other User Category');
        $this->otherUserCategory->setUser($this->otherUser);
        $this->em->persist($this->otherUserCategory);

        $this->faq = new Faqs();
        $this->faq->setName('Select Faq Form Test Faq');
        $this->faq->setCategory($this->category);
        $this->faq->setUser($this->user);
        $this->em->persist($this->faq);

        $this->em->flush();
    }

    protected function tearDown(): void
    {
        $categoryId = $this->category->getId();
        $otherCategoryId = $this->otherUserCategory->getId();
        $userId = $this->user->getId();
        $otherUserId = $this->otherUser->getId();
        $this->em->clear();

        foreach ([$categoryId, $otherCategoryId] as $id) {
            $category = $this->em->find(Categories::class, $id);
            if (null !== $category) {
                $this->em->remove($category);
            }
        }
        $this->em->flush();

        foreach ([$userId, $otherUserId] as $id) {
            $user = $this->em->find(Users::class, $id);
            if (null !== $user) {
                $this->em->remove($user);
            }
        }
        $this->em->flush();

        parent::tearDown();
    }

    private function createForm(?Faqs $faq = null): FormInterface
    {
        return $this->formFactory->create(SelectFaqFormType::class, null, [
            'user' => $this->user,
            'faq' => $faq,
            'csrf_protection' => false,
        ]);
    }

    public function testUserOptionIsRequired(): void
    {
        $this->expectException(InvalidOptionsException::class);

        $this->formFactory->create(SelectFaqFormType::class);
    }

    public function testSubmittingWithoutCategoryLeavesFaqEmptyAndInvalid(): void
    {
        $form = $this->createForm();
        $form->submit([
            'category' => '',
            'faq' => '',
            'run' => '',
        ]);

        $this->assertFalse($form->isValid());
        $this->assertFalse($form->get('faq')->isValid());
        $this->assertStringContainsString(
            'Ce champ doit être renseigné.',
            (string) $form->get('faq')->getErrors(true)
        );
    }

    public function testSubmittingWithOwnCategoryAndFaqIsValid(): void
    {
        $form = $this->createForm();
        $form->submit([
            'category' => (string) $this->category->getId(),
            'faq' => (string) $this->faq->getId(),
            'run' => '',
        ]);

        $this->assertTrue($form->isValid());
        $this->assertSame($this->category->getId(), $form->get('category')->getData()->getId());
        $this->assertSame($this->faq->getId(), $form->get('faq')->getData()->getId());
    }

    public function testRunButtonIsDetectedAsClicked(): void
    {
        $form = $this->createForm();
        $form->submit([
            'category' => (string) $this->category->getId(),
            'faq' => (string) $this->faq->getId(),
            'run' => '',
        ]);

        $runButton = $form->get('run');
        $editButton = $form->get('edit');
        $this->assertInstanceOf(SubmitButton::class, $runButton);
        $this->assertInstanceOf(SubmitButton::class, $editButton);
        $this->assertTrue($runButton->isClicked());
        $this->assertFalse($editButton->isClicked());
    }

    public function testEditButtonIsDetectedAsClicked(): void
    {
        $form = $this->createForm();
        $form->submit([
            'category' => (string) $this->category->getId(),
            'faq' => (string) $this->faq->getId(),
            'edit' => '',
        ]);

        $editButton = $form->get('edit');
        $runButton = $form->get('run');
        $this->assertInstanceOf(SubmitButton::class, $editButton);
        $this->assertInstanceOf(SubmitButton::class, $runButton);
        $this->assertTrue($editButton->isClicked());
        $this->assertFalse($runButton->isClicked());
    }

    public function testCategoryBelongingToAnotherUserIsRejected(): void
    {
        $form = $this->createForm();
        $form->submit([
            'category' => (string) $this->otherUserCategory->getId(),
            'faq' => '',
            'run' => '',
        ]);

        $this->assertFalse($form->isValid());
        $this->assertFalse($form->get('category')->isValid());
    }

    public function testPreSetDataPreselectsCategoryAndFaqFromFaqOption(): void
    {
        // MainController::index passe la faq courante (issue de la route) en option
        // 'faq' : PRE_SET_DATA doit alors pré-sélectionner catégorie et faq.
        $form = $this->createForm($this->faq);

        $this->assertSame($this->category->getId(), $form->get('category')->getData()->getId());
        $this->assertSame($this->faq->getId(), $form->get('faq')->getData()->getId());
    }
}
