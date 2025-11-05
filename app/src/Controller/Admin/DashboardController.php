<?php

namespace App\Controller\Admin;

use App\Entity\Product;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminRoute;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Response;

#[AdminDashboard(routePath: '/', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    public function index(): Response
    {
         return $this->redirectToRoute('admin_product_index');
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()->setTitle('Dummy Products with QR codes');
    }

    #[AdminRoute(name: 'product_show', path: '/show/{sku}')]
    public function show(
        #[MapEntity(mapping: ['sku' => 'sku'])] Product $product): Response
    {
        return $this->render('product.html.twig', ['product' => $product]);
//        dd($sku);

    }

    public function configureMenuItems(): iterable
    {
//        yield MenuItem::linkToDashboard('Dashboard', 'fa fa-home');
         yield MenuItem::linkToCrud('Products', 'fas fa-list', Product::class);
    }
}
