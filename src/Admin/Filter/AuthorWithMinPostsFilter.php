<?php

namespace App\Admin\Filter;

use App\Entity\Post;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Filter\FilterInterface;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FieldDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDataDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\FilterDto;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Contracts\Translation\TranslatableInterface;

/**
 * Custom filter to filter posts by authors who have published at least X posts.
 * This demonstrates how to create a custom filter with a subquery.
 */
final class AuthorWithMinPostsFilter implements FilterInterface
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
                'filter.min_posts.any' => 0,
                'filter.min_posts.1' => 1,
                'filter.min_posts.5' => 5,
                'filter.min_posts.10' => 10,
                'filter.min_posts.20' => 20,
            ],
            'placeholder' => 'filter.min_posts.placeholder',
        ]);

        return $filter;
    }

    public function setFormTypeOption(string $optionName, mixed $optionValue): self
    {
        $this->dto->setFormTypeOption($optionName, $optionValue);

        return $this;
    }

    public function apply(QueryBuilder $queryBuilder, FilterDataDto $filterDataDto, ?FieldDto $fieldDto, EntityDto $entityDto): void
    {
        $minPosts = (int) $filterDataDto->getValue();

        if ($minPosts <= 0) {
            return;
        }

        $alias = $filterDataDto->getEntityAlias();
        $parameterName = $filterDataDto->getParameterName();

        // Create a subquery to count published posts by author
        // Note: We always filter on the 'author' property regardless of the filter's property name
        $subQuery = $queryBuilder->getEntityManager()->createQueryBuilder()
            ->select('COUNT(sub_p.id)')
            ->from(Post::class, 'sub_p')
            ->where('sub_p.author = '.$alias.'.author')
            ->andWhere("sub_p.status = 'published'")
            ->getDQL();

        $queryBuilder
            ->andWhere(sprintf('(%s) >= :%s', $subQuery, $parameterName))
            ->setParameter($parameterName, $minPosts);
    }

    public function getAsDto(): FilterDto
    {
        return $this->dto;
    }
}
