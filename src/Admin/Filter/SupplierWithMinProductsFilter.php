<?php

namespace App\Admin\Filter;

use App\Entity\Product;
use App\Enum\ProductStatus;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Custom filter to find the products whose supplier has at least X published products.
 * This demonstrates how to create a custom filter with a subquery.
 */
final class SupplierWithMinProductsFilter implements FilterInterface
{
    private FilterDto $dto;

    private function __construct()
    {
        $dto = new FilterDto();
        $dto->setApplyCallable(fn (QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto) => $this->apply($queryBuilder, $filterDataDto, $fieldDto, $entityDto));

        $this->dto = $dto;
    }

    public function __toString(): string
    {
        return $this->dto->getProperty();
    }

    /**
     * @param TranslatableInterface|string|false|null $label
     */
    public static function new(string $propertyName, $label = null): self
    {
        $filter = new self();
        $filter->dto->setFqcn(__CLASS__);
        $filter->dto->setProperty($propertyName);
        $filter->dto->setLabel($label);
        $filter->dto->setFormType(ChoiceType::class);
        $filter->dto->setFormTypeOptions([
            'choices' => [
                'filter.min_products.any' => 0,
                'filter.min_products.1' => 1,
                'filter.min_products.3' => 3,
                'filter.min_products.5' => 5,
                'filter.min_products.8' => 8,
            ],
            'placeholder' => 'filter.min_products.placeholder',
        ]);

        return $filter;
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $minProducts = (int) $filterDataDto->getValue();

        if ($minProducts <= 0) {
            return;
        }

        $alias = $filterDataDto->getEntityAlias();
        $parameterName = $filterDataDto->getParameterName();

        // the filter's property name is virtual, so the subquery always compares the 'supplier' association
        $subQuery = $queryBuilder->getEntityManager()->createQueryBuilder()
            ->select('COUNT(sub_product.id)')
            ->from(Product::class, 'sub_product')
            ->where('sub_product.supplier = '.$alias.'.supplier')
            ->andWhere(sprintf("sub_product.status = '%s'", ProductStatus::Published->value))
            ->getDQL();

        $queryBuilder
            ->andWhere(sprintf('(%s) >= :%s', $subQuery, $parameterName))
            ->setParameter($parameterName, $minProducts);
    }

    public function getAsDto(): FilterDto
    {
        return $this->dto;
    }
}
