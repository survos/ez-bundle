<?php

namespace App\Entity;

use App\Repository\ProductRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Survos\EzBundle\Attribute\EzAdmin;
use Survos\EzBundle\Attribute\EzField;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
#[EzAdmin()]
class Product
{
    public function __construct(
        #[ORM\Id] #[ORM\Column] private(set) ?string $sku = null
    ) { }

    #[ORM\Column(length: 255)]
    #[EzField(type: Types::TEXT)]
    public ?string $title = null;

    #[ORM\Column()]
    #[EzField(type: Types::TEXT, filter: true)]
    public ?string $category = null;

    #[ORM\Column(type: Types::TEXT)]
    public ?string $description = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    public ?string $imageUrl = null;

    #[ORM\Column(nullable: true)]
    public ?int $stock = null;

}
