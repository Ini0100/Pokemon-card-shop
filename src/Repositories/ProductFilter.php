<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\Request;

/** Search criteria for the product catalog. */
final class ProductFilter
{
    public const SORT_OPTIONS = [
        'name_asc'   => 'Nombre (A-Z)',
        'name_desc'  => 'Nombre (Z-A)',
        'price_asc'  => 'Precio: menor a mayor',
        'price_desc' => 'Precio: mayor a menor',
    ];

    private const SORT_SQL = [
        'name_asc'   => 'p.name ASC',
        'name_desc'  => 'p.name DESC',
        'price_asc'  => 'p.price ASC, p.name ASC',
        'price_desc' => 'p.price DESC, p.name ASC',
    ];

    public const PER_PAGE = 24;

    /** @param list<int> $propertyValueIds */
    public function __construct(
        public readonly ?int $categoryId = null,
        public readonly string $search = '',
        public readonly array $propertyValueIds = [],
        public readonly ?float $minPrice = null,
        public readonly ?float $maxPrice = null,
        public readonly string $sort = 'name_asc',
        public readonly int $page = 1,
        public readonly int $perPage = self::PER_PAGE,
    ) {
    }

    public static function fromRequest(Request $request): self
    {
        $categoryId = $request->int('category');
        $sort       = $request->string('sort', 'name_asc');

        return new self(
            categoryId: $categoryId > 0 ? $categoryId : null,
            search: mb_substr($request->string('q'), 0, 100),
            propertyValueIds: $request->intList('values'),
            minPrice: $request->float('min_price'),
            maxPrice: $request->float('max_price'),
            sort: array_key_exists($sort, self::SORT_SQL) ? $sort : 'name_asc',
            page: max(1, $request->int('page', 1)),
        );
    }

    public function offset(): int
    {
        return ($this->page - 1) * $this->perPage;
    }

    /**
     * Query-string parameters reproducing this filter, for pagination links.
     *
     * @return array<string, mixed>
     */
    public function toQuery(array $overrides = []): array
    {
        $query = array_filter([
            'category'  => $this->categoryId,
            'q'         => $this->search,
            'values'    => $this->propertyValueIds,
            'min_price' => $this->minPrice,
            'max_price' => $this->maxPrice,
            'sort'      => $this->sort === 'name_asc' ? null : $this->sort,
            'page'      => $this->page > 1 ? $this->page : null,
        ], static fn (mixed $value): bool => $value !== null && $value !== '' && $value !== []);

        return array_filter(array_replace($query, $overrides), static fn (mixed $value): bool => $value !== null);
    }

    /** True when the user has narrowed the results (category excluded). */
    public function isFiltered(): bool
    {
        return $this->search !== ''
            || $this->propertyValueIds !== []
            || $this->minPrice !== null
            || $this->maxPrice !== null;
    }

    public function orderBySql(): string
    {
        return self::SORT_SQL[$this->sort];
    }
}
