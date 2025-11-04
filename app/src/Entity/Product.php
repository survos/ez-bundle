<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{
    public function __construct(
        #[ORM\Id]
        #[ORM\Column]
        private(set) ?string $sku = null
    )
    {

    }

    #[ORM\Column(length: 255)]
    public ?string $title = null;

    #[ORM\Column(type: Types::TEXT)]
    public ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $imageUrl = null;

    #[ORM\Column(nullable: true)]
    public ?int $stock = null;

}
