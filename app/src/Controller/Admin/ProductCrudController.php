<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AvatarField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
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
        yield TextField::new('title');
        yield ImageField::new('imageUrl', 'Product Image');
        yield ImageField::new('sku', 'QR')
            ->formatValue(function ($value, Product $entity) {
                $productUrl = $this->urlGenerator->generate('admin_product_show', ['sku' => $value]);
                $qrUrl = $this->urlGenerator->generate('qr_code_generate', [
                    'data' => $productUrl,
                    'builder' => 'default'
                    ],
                    UrlGeneratorInterface::ABSOLUTE_URL
                );
                return $qrUrl;
            });
    }
}
