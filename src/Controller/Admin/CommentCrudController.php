<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;

class CommentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Comment::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();
        yield DateTimeField::new('publishedAt')->onlyOnIndex();
        yield TextareaField::new('content')->onlyOnForms();
        yield AssociationField::new('author')
            ->setSortProperty('fullName')
            ->onlyOnForms();
        yield AssociationField::new('post')
            ->setSortProperty('title')
            ->onlyOnForms();
        yield DateTimeField::new('publishedAt')->onlyOnForms();
    }
}
