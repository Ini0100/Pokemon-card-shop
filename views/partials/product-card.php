<?php /** @var array $product */ ?>
<?php $stock = (int) $product['stock']; ?>
<a href="<?= url('/products/' . $product['id']) ?>" class="product-card<?= $stock === 0 ? ' is-sold-out' : '' ?>">
    <div class="product-card__media">
        <img src="<?= product_image($product['image_path']) ?>" alt="" loading="lazy">
        <?php if ($stock === 0): ?>
            <span class="tag tag--overlay">Agotado</span>
        <?php elseif ($stock <= 3): ?>
            <span class="tag tag--overlay tag--warning">¡Últimas <?= $stock ?>!</span>
        <?php endif; ?>
    </div>
    <div class="product-card__body">
        <h3 class="product-card__name"><?= e($product['name']) ?></h3>
        <?php if (!empty($product['collection'])): ?>
            <p class="product-card__meta"><?= e($product['collection']) ?></p>
        <?php endif; ?>
        <p class="product-card__price"><?= money($product['price']) ?></p>
    </div>
</a>
