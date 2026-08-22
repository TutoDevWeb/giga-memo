<?php

namespace App\Tests\Form;

use App\Entity\Categories;
use App\Entity\Faqs;
use App\Entity\Users;
use App\Form\FaqFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;

/**
 * Ces tests couvrent FaqFormType : option "user" obligatoire, mapping des
 * champs "name"/"category"/"duration", et filtrage des catégories proposées
 * (query_builder restreint à l'utilisateur passé en option).
 */
class FaqFormTypeTest extends KernelTestCase
{
    private EntityManagerInterface $em;
    private FormFactoryInterface $formFactory;
    private Users $user;
    private Users $otherUser;
    private Categories $category;
    private Categories $otherUserCategory;

    protected function setUp(): void
    {
        self::bootKernel();

        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->formFactory = self::getContainer()->get(FormFactoryInterface::class);

        $this->user = new Users();
        $this->user->setEmail('faq-form-test-'.uniqid().'@example.com');
        $this->user->setPassword('not-used');
        $this->em->persist($this->user);

        $this->otherUser = new Users();
        $this->otherUser->setEmail('faq-form-other-'.uniqid().'@example.com');
        $this->otherUser->setPassword('not-used');
        $this->em->persist($this->otherUser);

        $this->category = new Categories();
        $this->category->setName('Faq Form Test Category');
        $this->category->setUser($this->user);
        $this->em->persist($this->category);

        $this->otherUserCategory = new Categories();
        $this->otherUserCategory->setName('Faq Form Other User Category');
        $this->otherUserCategory->setUser($this->otherUser);
        $this->em->persist($this->otherUserCategory);

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

    private function submit(array $data, ?Users $user = null): FormInterface
    {
        $faq = new Faqs();
        $faq->setUser($user ?? $this->user);

        $form = $this->formFactory->create(FaqFormType::class, $faq, [
            'user' => $user ?? $this->user,
            'csrf_protection' => false,
        ]);
        $form->submit($data);

        return $form;
    }

    public function testUserOptionIsRequired(): void
    {
        // 'user' a un défaut (null) mais setAllowedTypes force une instance de Users :
        // sans l'option, la résolution échoue avec InvalidOptionsException plutôt
        // qu'avec MissingOptionsException.
        $this->expectException(InvalidOptionsException::class);

        $this->formFactory->create(FaqFormType::class, new Faqs());
    }

    public function testValidDataIsAccepted(): void
    {
        $form = $this->submit([
            'name' => 'Ma FAQ',
            'category' => (string) $this->category->getId(),
            'duration' => ['minutes' => '10'],
        ]);

        $this->assertTrue($form->isValid());
        $faq = $form->getData();
        $this->assertSame('Ma FAQ', $faq->getName());
        $this->assertSame($this->category->getId(), $faq->getCategory()->getId());
        $this->assertSame(10, $faq->getDuration()->i);
    }

    public function testEmptyNameIsRejected(): void
    {
        $form = $this->submit([
            'name' => '',
            'category' => (string) $this->category->getId(),
            'duration' => ['minutes' => '10'],
        ]);

        $this->assertFalse($form->isValid());
        $this->assertStringContainsString('Ce champ doit être renseigné.', (string) $form->getErrors(true));
    }

    public function testCategoryBelongingToAnotherUserIsRejected(): void
    {
        // Le query_builder de FaqFormType ne propose que les catégories de
        // l'utilisateur passé en option : une catégorie d'un autre utilisateur
        // n'est pas un choix valide, même en forgeant l'id dans la requête.
        $form = $this->submit([
            'name' => 'Ma FAQ',
            'category' => (string) $this->otherUserCategory->getId(),
            'duration' => ['minutes' => '10'],
        ]);

        $this->assertFalse($form->isValid());
        $this->assertFalse($form->get('category')->isValid());
    }

    public function testMissingCategoryIsRejected(): void
    {
        $form = $this->submit([
            'name' => 'Ma FAQ',
            'duration' => ['minutes' => '10'],
        ]);

        $this->assertFalse($form->isValid());
    }
}
