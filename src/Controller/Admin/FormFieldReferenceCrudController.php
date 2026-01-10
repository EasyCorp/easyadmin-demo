<?php

namespace App\Controller\Admin;

use App\Entity\FormFieldReference;
use App\Entity\Post;
use App\Entity\User;
use App\Form\Type\CollectionComplexType;
use App\Form\Type\CollectionSimpleType;
use App\Form\Type\TagsInputType;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CodeEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ColorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CountryField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CurrencyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\HiddenField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\LanguageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\LocaleField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\PercentField;
use EasyCorp\Bundle\EasyAdminBundle\Field\SlugField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimezoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\UrlField;

class FormFieldReferenceCrudController extends AbstractCrudController
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public static function getEntityFqcn(): string
    {
        return FormFieldReference::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle(Crud::PAGE_NEW, 'menu.form_field_reference');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_RETURN)
            ->remove(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER)
        ;
    }

    public function configureFields(string $pageName): iterable
    {
        $choices = ['choice.1' => 0, 'choice.2' => 1, 'choice.3' => 2];

        return [
            FormField::addFieldset('field_group.text'),
            TextField::new('text', 'field.text'),
            SlugField::new('slug', 'field.slug')->setTargetFieldName('text'),
            TextareaField::new('textarea', 'field.textarea'),
            TextEditorField::new('textEditor', 'field.text_editor'),
            CodeEditorField::new('codeEditor', 'field.code_editor')->setNumOfRows(12)->setLanguage('php'),

            FormField::addFieldset('field_group.choice'),
            BooleanField::new('boolean', 'field.boolean'),
            ChoiceField::new('autocomplete', 'field.choice_autocomplete')->setChoices($choices)->allowMultipleChoices()->autocomplete(),
            ChoiceField::new('checkbox', 'field.choice_checkbox')->setChoices($choices)->allowMultipleChoices()->renderExpanded(),
            ChoiceField::new('radiobutton', 'field.choice_radiobutton')->setChoices($choices)->renderExpanded(),

            FormField::addFieldset('field_group.numeric'),
            IntegerField::new('integer', 'field.integer'),
            NumberField::new('decimal', 'field.number'),
            PercentField::new('percent', 'field.percent')->setColumns(2),
            FormField::addRow(),
            MoneyField::new('money', 'field.money')->setCurrency('EUR')->setColumns(3),

            FormField::addFieldset('field_group.datetime'),
            DateField::new('date', 'field.date'),
            TimeField::new('time', 'field.time'),
            DateTimeField::new('datetime', 'field.datetime'),
            TimezoneField::new('timezone', 'field.timezone'),

            FormField::addFieldset('field_group.i18n'),
            CountryField::new('country', 'field.country'),
            CurrencyField::new('currency', 'field.currency'),
            LanguageField::new('language', 'field.language'),
            LocaleField::new('locale', 'field.locale'),

            FormField::addFieldset('field_group.association'),
            ArrayField::new('array', 'field.array'),
            AssociationField::new('author', 'field.association'),
            CollectionField::new('collectionSimple', 'field.collection_simple')->setFormTypeOption('entry_type', CollectionSimpleType::class),
            CollectionField::new('collectionComplex', 'field.collection_complex')->setFormTypeOption('entry_type', CollectionComplexType::class)->setEntryIsComplex(true),

            FormField::addFieldset('field_group.image'),
            ImageField::new('image', 'field.image')->setUploadDir('/public/images/'),

            FormField::addFieldset('field_group.other'),
            IdField::new('id', 'field.id')->setColumns(2),
            FormField::addRow(),
            ColorField::new('color', 'field.color'),
            EmailField::new('email', 'field.email'),
            TelephoneField::new('telephone', 'field.telephone'),
            UrlField::new('url', 'field.url'),
        ];
    }

    public function createEntity(string $entityFqcn)
    {
        $janeDoe = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'jane_admin']);
        $johnDoe = $this->entityManager->getRepository(User::class)->findOneBy(['username' => 'john_user']);
        $formFieldReference = parent::createEntity($entityFqcn);

        $formFieldReference->author = $janeDoe;

        $formFieldReference->collectionSimple = [$janeDoe, $johnDoe];
        $formFieldReference->collectionComplex = [$janeDoe, $johnDoe];

        return $formFieldReference;
    }
}
