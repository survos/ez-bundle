<?php

namespace App\Controller;

use App\Repository\ProductRepository;
use Symfony\Bridge\Twig\Attribute\Template;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

class AppController
{
	public function __construct(
        private ProductRepository $productRepository,
    )
	{
	}


	#[Route(path: '/product_show/{sku}', name: 'product_show')]
	#[Template('app/product_show.html.twig')]
	public function product_show(Request $request, string $sku): array|Response
	{
        if (!$product = $this->productRepository->find($sku)) {
           throw new NotFoundHttpException('Product not found ' . $sku);
        }
		return ['product' => $product];
	}
}
