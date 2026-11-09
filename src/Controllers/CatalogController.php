<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductFilter;
use App\Repositories\ProductRepository;
use App\Services\CartService;

final class CatalogController extends Controller
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly CategoryRepository $categories,
        private readonly CartService $cart,
    ) {
    }

    public function index(Request $request): void
    {
        // Keep displayed stock accurate by returning abandoned reservations first.
        $this->cart->releaseExpiredReservations();

        $filter   = ProductFilter::fromRequest($request);
        $category = $filter->categoryId !== null ? $this->categories->find($filter->categoryId) : null;

        if ($filter->categoryId !== null && $category === null) {
            throw HttpException::notFound('La categoría no existe.');
        }

        $isHome = $category === null && !$filter->isFiltered() && $filter->page === 1;
        $result = $this->products->paginate($filter);
        $pages  = max(1, (int) ceil($result['total'] / $filter->perPage));

        if ($filter->page > $pages) {
            throw HttpException::notFound('La página solicitada no existe.');
        }

        $this->render('catalog/index', [
            'title'          => $category['name'] ?? ($isHome ? null : 'Catálogo'),
            'filter'         => $filter,
            'category'       => $category,
            'filters'        => $category ? $this->categories->filtersFor((int) $category['id']) : [],
            'products'       => $result['items'],
            'total'          => $result['total'],
            'pages'          => $pages,
            'featuredGroups' => $isHome ? $this->categories->allWithCovers() : [],
            'heroCards'      => $isHome ? $this->mostValuableCards(3) : [],
        ]);
    }

    /** @return list<array<string, mixed>> */
    private function mostValuableCards(int $limit): array
    {
        $cards = $this->categories->findBySlug('cards');

        if ($cards === null) {
            return [];
        }

        $filter = new ProductFilter(categoryId: (int) $cards['id'], sort: 'price_desc', perPage: $limit);

        return $this->products->paginate($filter)['items'];
    }

    public function show(Request $request, int $id): void
    {
        $this->cart->releaseExpiredReservations();

        $product = $this->products->find($id) ?? throw HttpException::notFound('El producto no existe.');

        $this->render('catalog/show', [
            'title'      => $product['name'],
            'product'    => $product,
            'attributes' => $this->products->attributes($id),
        ]);
    }
}
