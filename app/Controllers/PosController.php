<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Models/Sale.php';

final class PosController
{
    private Sale $sales;

    public function __construct()
    {
        $this->sales = new Sale();
    }

    public function index(array $params = []): void
    {
        $this->requireAuthenticatedUser(jsonResponse: false);
        $this->view('pos/index');
    }

    public function store(array $params = []): void
    {
        $this->requireAuthenticatedUser(jsonResponse: true);

        if (!$this->isJsonRequest()) {
            $this->json(['success' => false, 'message' => 'Requete JSON attendue.'], 415);
        }

        try {
            $payload = $this->jsonPayload();
            $result = $this->sales->createFromPos($payload, $this->currentShopId(), $this->currentUserId());

            $this->json([
                'success' => true,
                'message' => 'Vente validee avec succes.',
                'data' => $result,
            ], 201);
        } catch (InvalidArgumentException $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 422);
        } catch (Throwable $exception) {
            $this->json(['success' => false, 'message' => $exception->getMessage()], 400);
        }
    }

    private function jsonPayload(): array
    {
        $raw = file_get_contents('php://input');

        if ($raw === false || trim($raw) === '') {
            throw new InvalidArgumentException('Corps JSON vide.');
        }

        $payload = json_decode($raw, true);

        if (!is_array($payload) || json_last_error() !== JSON_ERROR_NONE) {
            throw new InvalidArgumentException('JSON invalide.');
        }

        return $payload;
    }

    private function isJsonRequest(): bool
    {
        $contentType = strtolower((string) ($_SERVER['CONTENT_TYPE'] ?? ''));
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT'] ?? ''));

        return str_contains($contentType, 'application/json') || str_contains($accept, 'application/json');
    }

    private function requireAuthenticatedUser(bool $jsonResponse): void
    {
        $this->startSession();

        if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
            if (!$jsonResponse) {
                $this->redirect('/login');
            }

            $this->json(['success' => false, 'message' => 'Authentification requise.'], 401);
        }

        if ($this->currentShopId() <= 0) {
            if (!$jsonResponse) {
                http_response_code(403);
                echo 'Boutique non definie pour cet utilisateur.';
                exit;
            }

            $this->json(['success' => false, 'message' => 'Boutique non definie pour cet utilisateur.'], 403);
        }
    }

    private function currentShopId(): int
    {
        $this->startSession();

        return (int) ($_SESSION['user']['shop_id'] ?? 0);
    }

    private function currentUserId(): int
    {
        $this->startSession();

        return (int) ($_SESSION['user']['id'] ?? 0);
    }

    private function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $file = dirname(__DIR__) . '/Views/' . $view . '.php';

        if (!is_file($file)) {
            http_response_code(500);
            echo 'Vue introuvable.';
            exit;
        }

        require $file;
    }

    private function json(array $payload, int $statusCode = 200): never
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_THROW_ON_ERROR);
        exit;
    }

    private function redirect(string $path): never
    {
        header('Location: ' . $path, true, 302);
        exit;
    }

    private function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start([
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax',
                'use_strict_mode' => true,
            ]);
        }
    }
}
