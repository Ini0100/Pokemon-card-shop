<?php
/**
 * @var list<array> $boosters
 * @var list<array> $cards
 * @var list<array> $openedCards
 */
use App\Services\AlbumService;
?>
<header class="page-header">
    <h1>Mi álbum</h1>
    <p class="muted">Los sobres y cartas que compras se guardan aquí. Abre un sobre para conseguir <?= AlbumService::CARDS_PER_BOOSTER ?> cartas de su colección.</p>
</header>

<?php if ($openedCards !== []): ?>
    <section class="card reveal">
        <h2>¡Has abierto un sobre!</h2>
        <div class="album-grid">
            <?php foreach ($openedCards as $card): ?>
                <figure class="album-item album-item--new">
                    <img src="<?= product_image($card['image_path'], 'high') ?>" alt="<?= e($card['name']) ?>">
                    <figcaption><?= e($card['name']) ?></figcaption>
                </figure>
            <?php endforeach; ?>
        </div>
    </section>
<?php endif; ?>

<section class="album-section">
    <h2>Sobres sin abrir</h2>
    <?php if ($boosters === []): ?>
        <p class="empty-state">No tienes sobres. <a href="<?= url('/', ['category' => 1]) ?>">Ver sobres</a></p>
    <?php else: ?>
        <div class="album-grid">
            <?php foreach ($boosters as $booster): ?>
                <figure class="album-item">
                    <img src="<?= product_image($booster['image_path']) ?>" alt="<?= e($booster['name']) ?>">
                    <figcaption>
                        <?= e($booster['name']) ?>
                        <span class="badge">×<?= (int) $booster['quantity'] ?></span>
                    </figcaption>
                    <form method="post" action="<?= url('/album/boosters/' . $booster['id'] . '/open') ?>">
                        <?= csrf_field() ?>
                        <button type="submit" class="button button--primary button--small button--block">Abrir sobre</button>
                    </form>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<section class="album-section">
    <h2>Colección de cartas</h2>
    <?php if ($cards === []): ?>
        <p class="empty-state">Aún no tienes cartas.</p>
    <?php else: ?>
        <div class="album-grid">
            <?php foreach ($cards as $card): ?>
                <figure class="album-item">
                    <img src="<?= product_image($card['image_path']) ?>" alt="<?= e($card['name']) ?>" loading="lazy">
                    <figcaption>
                        <?= e($card['name']) ?>
                        <?php if ((int) $card['quantity'] > 1): ?>
                            <span class="badge">×<?= (int) $card['quantity'] ?></span>
                        <?php endif; ?>
                    </figcaption>
                    <?php if ($card['collection']): ?>
                        <span class="tag"><?= e($card['collection']) ?></span>
                    <?php endif; ?>
                </figure>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>
