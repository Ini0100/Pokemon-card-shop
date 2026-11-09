<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\HttpException;
use App\Core\Request;
use App\Core\ValidationException;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Services\Auth;
use App\Services\CartService;
use App\Services\ImageUploader;
use App\Services\RegistrationService;

final class AdminController extends Controller
{
    public function __construct(
        private readonly Auth $auth,
        private readonly UserRepository $users,
        private readonly ProductRepository $products,
        private readonly CategoryRepository $categories,
        private readonly CartService $cart,
        private readonly ImageUploader $uploader,
    ) {
    }

    /**
     * @param array<string, string> $errors
     * @param array<string, mixed>  $old
     */
    public function index(Request $request, array $errors = [], array $old = [], int $status = 200): void
    {
        $this->auth->requireAdmin();
        $this->cart->releaseExpiredReservations();

        $this->render('admin/index', [
            'title'      => 'Administración',
            'users'      => $this->users->all(),
            'products'   => $this->products->allWithCategories(),
            'categories' => $this->categories->all(),
            'errors'     => $errors,
            'old'        => $old,
        ], $status);
    }

    public function storeUser(Request $request): void
    {
        $this->auth->requireAdmin();

        $email    = mb_strtolower($request->string('email'));
        $password = (string) $request->input('password', '');
        $role     = $request->string('role') === 'admin' ? 'admin' : 'customer';

        try {
            RegistrationService::validateCredentials($email, $password);

            if ($this->users->emailExists($email)) {
                throw ValidationException::withMessage('Ya existe una cuenta con este correo electrónico.', 'email');
            }
        } catch (ValidationException $e) {
            $this->index($request, $e->errors, ['user' => ['email' => $email, 'role' => $role]], 422);

            return;
        }

        $this->users->create($email, password_hash($password, PASSWORD_DEFAULT), $role, true);
        $this->flash('success', "Usuario {$email} creado.");
        $this->redirect('/admin');
    }

    public function deleteUser(Request $request, int $id): void
    {
        $adminId = $this->auth->requireAdmin();

        if ($id === $adminId) {
            $this->flash('error', 'No puedes eliminar tu propia cuenta.');
            $this->redirect('/admin');
        }

        // Return reserved stock before the cart lines are removed by the cascade.
        $this->cart->clear($id);
        $this->users->delete($id);

        $this->flash('success', 'Usuario eliminado.');
        $this->redirect('/admin');
    }

    public function userCart(Request $request, int $id): void
    {
        $this->auth->requireAdmin();
        $this->cart->releaseExpiredReservations();

        $user  = $this->users->find($id) ?? throw HttpException::notFound('El usuario no existe.');
        $items = $this->cart->items($id);

        $this->render('admin/user-cart', [
            'title'  => 'Carrito de ' . $user['email'],
            'user'   => $user,
            'items'  => $items,
            'totals' => CartService::totals($items),
        ]);
    }

    public function clearUserCart(Request $request, int $id): void
    {
        $this->auth->requireAdmin();

        $this->cart->clear($id);

        $this->flash('success', 'Carrito vaciado y stock restaurado.');
        $this->redirect("/admin/users/{$id}/cart");
    }

    public function storeProduct(Request $request): void
    {
        $this->auth->requireAdmin();

        $data = [
            'name'          => $request->string('name'),
            'description'   => $request->string('description'),
            'price'         => $request->float('price'),
            'shipping_cost' => $request->float('shipping_cost'),
            'stock'         => $request->int('stock', -1),
        ];
        $categoryId = $request->int('category_id');

        try {
            $this->validateProduct($data, $categoryId);
            $image = $request->file('image') ?? throw ValidationException::withMessage('Selecciona una imagen.', 'image');
            $data['image_path'] = $this->uploader->store($image);
        } catch (ValidationException $e) {
            $this->index($request, $e->errors, ['product' => $data + ['category_id' => $categoryId]], 422);

            return;
        }

        $this->products->create($data, $categoryId);

        $this->flash('success', "Producto «{$data['name']}» creado.");
        $this->redirect('/admin');
    }

    public function deleteProduct(Request $request, int $id): void
    {
        $this->auth->requireAdmin();

        $product = $this->products->find($id) ?? throw HttpException::notFound('El producto no existe.');

        $this->products->delete($id);
        $this->uploader->delete($product['image_path']);

        $this->flash('success', "Producto «{$product['name']}» eliminado.");
        $this->redirect('/admin');
    }

    /** @param array<string, mixed> $data */
    private function validateProduct(array $data, int $categoryId): void
    {
        $errors = [];

        if ($data['name'] === '' || mb_strlen($data['name']) > 255) {
            $errors['name'] = 'El nombre es obligatorio (máx. 255 caracteres).';
        }

        if ($data['price'] === null || $data['price'] <= 0) {
            $errors['price'] = 'El precio debe ser mayor que 0.';
        }

        if ($data['shipping_cost'] === null || $data['shipping_cost'] < 0) {
            $errors['shipping_cost'] = 'El coste de envío no puede ser negativo.';
        }

        if ($data['stock'] < 0) {
            $errors['stock'] = 'El stock debe ser un número entero mayor o igual que 0.';
        }

        if ($this->categories->find($categoryId) === null) {
            $errors['category_id'] = 'Selecciona una categoría válida.';
        }

        if ($errors !== []) {
            throw new ValidationException($errors);
        }
    }
}
