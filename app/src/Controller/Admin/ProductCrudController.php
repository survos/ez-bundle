<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Survos\EzBundle\Controller\BaseCrudController;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductCrudController extends BaseCrudController
{

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('sku');
        yield ImageField::new('imageUrl', 'Image');
        yield TextField::new('title');
        yield TextField::new('category');
        yield IntegerField::new('stock');
        yield ImageField::new('sku', 'QR')
            ->addCssClass('d-none d-md-table-cell')
            ->formatValue(function ($value) {
                $productUrl = $this->urlGenerator->generate('admin_product_show', ['sku' => $value], UrlGeneratorInterface::ABSOLUTE_URL);
                $qrUrl = $this->urlGenerator->generate('qr_code_generate', [
                    'data' => $productUrl,
                    'builder' => 'default'
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                return $qrUrl;
            });
    }

    public function configureActions(Actions $actions): Actions
    {
        $mobileViewAction = Action::new('mobile', "Mobile", 'fa fa-mobile')
            ->linkToUrl(function ($entity) {
                return $this->generateUrl('admin_product_show', ['sku' => $entity->sku]);
            })
//            ->setHtmlAttributes(['target' => '_blank']);
        ;

        return parent::configureActions($actions)
            ->add(Crud::PAGE_INDEX, $mobileViewAction);
    }

}
