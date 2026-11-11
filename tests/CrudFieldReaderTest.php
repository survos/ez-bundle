<?php

declare(strict_types=1);

namespace Survos\EzBundle\Tests;

use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use PHPUnit\Framework\TestCase;
use Survos\EzBundle\Controller\AbstractEzCrudController;
use Survos\EzBundle\Controller\BaseCrudController;
use Survos\EzBundle\Service\CrudFieldReader;
use Survos\FieldBundle\Attribute\Field;
use Survos\FieldBundle\Service\FieldReader;
use Symfony\Component\Form\Attribute\FormField;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Forms;
use Symfony\Component\Form\TypeInfoFormTypeGuesser;

final class CrudFieldReaderTest extends TestCase
{
    private function reader(): CrudFieldReader
    {
        return new CrudFieldReader(new FieldReader(), $this->factory(), $this->createStub(ManagerRegistry::class));
    }

    private function factory(): \Symfony\Component\Form\FormFactoryInterface
    {
        return Forms::createFormFactoryBuilder()->addTypeGuesser(new TypeInfoFormTypeGuesser())->getFormFactory();
    }

    public function testVisibilityOrderingAndNoUnannotatedFallback(): void
    {
        $reader = $this->reader();
        $fields = iterator_to_array($reader->fields(ReaderFixture::class, Crud::PAGE_INDEX));
        self::assertSame(['count', 'label'], array_map(fn ($field) => $field->getAsDto()->getProperty(), $fields));
        self::assertFalse($fields[0]->getAsDto()->isSortable());
        $controller = new class extends BaseCrudController {
            public static function getEntityFqcn(): string
            {
                return ReaderFixture::class;
            }
        };
        $controller->setCrudFieldReader($reader);
        self::assertCount(2, iterator_to_array($controller->configureFields(Crud::PAGE_DETAIL)));
    }

    public function testNativeGuessesAliasesAndOriginalValuesSurvive(): void
    {
        $factory = $this->factory();
        $input = new ReaderFixture();
        $form = $factory->createBuilder(FormType::class, $input);
        $fields = iterator_to_array($this->reader()->fields(ReaderFixture::class, Crud::PAGE_EDIT));
        self::assertCount(3, $fields);
        foreach ($fields as $field) {
            $dto = $field->getAsDto();
            self::assertArrayNotHasKey('data', $dto->getFormTypeOptions());
            $form->add($dto->getProperty(), $dto->getFormType(), $dto->getFormTypeOptions());
        }
        $form = $form->getForm();
        self::assertSame(7, $form->get('count')->getData());
        self::assertSame('Original', $form->get('caption')->getData());
        self::assertFalse($form->get('when')->isRequired());
        $form->submit(['count' => '9', 'caption' => 'Changed', 'when' => '']);
        self::assertTrue($form->isValid());
        self::assertSame(9, $input->count);
        self::assertSame('Changed', $input->label);
        self::assertNull($input->when);
    }

    public function testAllHiddenFieldsDoNotFallBackToDoctrineDefaults(): void
    {
        $reader = $this->reader();
        self::assertTrue($reader->supports(HiddenFixture::class));
        self::assertSame([], iterator_to_array($reader->fields(HiddenFixture::class, Crud::PAGE_INDEX)));
        self::assertSame([], iterator_to_array($reader->fields(HiddenFixture::class, Crud::PAGE_EDIT)));
    }

    public function testThinControllerOverridesWin(): void
    {
        $controller = new class extends AbstractEzCrudController {
            public static function getEntityFqcn(): string
            {
                return ReaderFixture::class;
            }

            protected function isReadOnly(): bool
            {
                return false;
            }

            protected function preferredFields(string $pageName): iterable
            {
                yield TextField::new('label', 'Override');
            }
        };
        $controller->setCrudFieldReader($this->reader());
        $fields = iterator_to_array($controller->configureFields(Crud::PAGE_INDEX));
        self::assertCount(2, $fields);
        self::assertSame('Override', $fields[0]->getAsDto()->getLabel());
    }
}

final class ReaderFixture
{
    #[Field(order: 20)]
    #[FormField(name: 'caption')]
    public string $label = 'Original';

    #[Field(order: 10, sortable: true)]
    #[FormField]
    public int $count = 7;

    #[Field(visible: false)]
    public array $embedding = [0.1];

    #[FormField]
    public ?\DateTimeImmutable $when = null;

    public string $internal = 'hidden';
}

final class HiddenFixture
{
    #[Field(visible: false)]
    public array $embedding = [];
}
