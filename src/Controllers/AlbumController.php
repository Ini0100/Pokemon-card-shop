<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Request;
use App\Core\Session;
use App\Core\ValidationException;
use App\Repositories\AlbumRepository;
use App\Services\AlbumService;
use App\Services\Auth;

final class AlbumController extends Controller
{
    private const OPENED_CARDS_KEY = 'album.opened_cards';

    public function __construct(
        private readonly Auth $auth,
        private readonly Session $session,
        private readonly AlbumRepository $album,
        private readonly AlbumService $albumService,
    ) {
    }

    public function index(): void
    {
        $userId = $this->auth->requireUser();

        $openedCards = $this->session->get(self::OPENED_CARDS_KEY, []);
        $this->session->forget(self::OPENED_CARDS_KEY);

        $this->render('album/index', [
            'title'       => 'Mi álbum',
            'boosters'    => $this->album->itemsInCategory($userId, 'boosters'),
            'cards'       => $this->album->itemsInCategory($userId, 'cards'),
            'openedCards' => $openedCards,
        ]);
    }

    public function openBooster(Request $request, int $id): void
    {
        $userId = $this->auth->requireUser();

        try {
            $cards = $this->albumService->openBooster($userId, $id);
            $this->session->set(self::OPENED_CARDS_KEY, $cards);
        } catch (ValidationException $e) {
            $this->flash('error', $e->getMessage());
        }

        $this->redirect('/album');
    }
}
