<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\Response;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductFilter;
use App\Repositories\ProductRepository;

/** Public, read-only JSON API over the product catalog. */
final class CatalogApiController
{
    public function __construct(
        private readonly ProductRepository $products,
        private readonly CategoryRepository $categories,
    ) {
    }

    public function categories(): void
    {
        Response::json(['data' => array_map(self::presentCategory(...), $this->categories->all())]);
    }

    public function filters(Request $request, int $id): void
    {
        $this->categories->find($id) ?? throw HttpException::notFound('La categoría no existe.');

        Response::json(['data' => $this->categories->filtersFor($id)]);
    }

    /** Accepts the same query parameters as the catalog page: category, q, values[], min_price, max_price, sort, page. */
    public function products(Request $request): void
    {
        $filter = ProductFilter::fromRequest($request);
        $result = $this->products->paginate($filter);

        Response::json([
            'data' => array_map(self::presentProduct(...), $result['items']),
            'meta' => [
                'page'      => $filter->page,
                'per_page'  => $filter->perPage,
                'total'     => $result['total'],
                'last_page' => max(1, (int) ceil($result['total'] / $filter->perPage)),
            ],
        ]);
    }

    public function product(Request $request, int $id): void
    {
        $product = $this->products->find($id) ?? throw HttpException::notFound('El producto no existe.');

        Response::json(['data' => self::presentProduct($product) + [
            'description'   => $product['description'],
            'shipping_cost' => (float) $product['shipping_cost'],
            'attributes'    => $this->products->attributes($id),
        ]]);
    }

    /** @param array<string, mixed> $category */
    private static function presentCategory(array $category): array
    {
        return ['id' => (int) $category['id'], 'slug' => $category['slug'], 'name' => $category['name']];
    }

    /** @param array<string, mixed> $product */
    private static function presentProduct(array $product): array
    {
        return [
            'id'        => (int) $product['id'],
            'name'      => $product['name'],
            'price'     => (float) $product['price'],
            'stock'     => (int) $product['stock'],
            'image_url' => preg_match('#^https?://#', $product['image_path'])
                ? product_image($product['image_path'], 'high')
                : absolute_url($product['image_path']),
        ];
    }
}
