<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Validator.php';
require_once dirname(__DIR__) . '/Models/Supply.php';

final class SupplyController
{
    private Supply $supplies;

    public function __construct()
    {
        $this->supplies = new Supply();
    }

    public function index(array $params = []): void
    {
        $this->requireManager();
        $supplies = $this->supplies->allByShop($this->currentShopId());

        $this->view('supplies/index', compact('supplies'));
    }

    public function create(array $params = []): void
    {
        $this->requireManager();
        $this->view('supplies/create');
    }

    public function store(array $params = []): void
    {
        $this->requireManager();

        $data = $this->payload();
        $errors = $this->validateArrival($data);

        if ($errors !== []) {
            $this->flashErrors($errors);
            $this->redirect('/supplies/create');
        }

        try {
            $supplyId = $this->supplies->createArrival($data, $this->currentShopId(), $this->currentUserId());
            $this->flashSuccess('Arrivage valide avec succes.');
            $this->redirect('/supplies/' . $supplyId);
        } catch (Throwable $exception) {
            $this->flashError('Impossible de valider l arrivage: ' . $exception->getMessage());
            $this->redirect('/supplies/create');
        }
    }

    public function show(array $params = []): void
    {
        $this->requireManager();
        $id = (int) ($params['id'] ?? 0);

        if ($id <= 0) {
            $this->abort(404, 'Arrivage introuvable.');
        }

        $supply = $this->supplies->findByShop($id, $this->currentShopId());

        if ($supply === null) {
            $this->abort(404, 'Arrivage introuvable pour cette boutique.');
        }

        $this->view('supplies/show', compact('supply'));
    }

    private function payload(): array
    {
        return [
            'supplier_id' => $_POST['supplier_id'] ?? null,
            'numero_arrivage' => $_POST['numero_arrivage'] ?? '',
            'date_approvisionnement' => $_POST['date_approvisionnement'] ?? null,
            'items' => $this->itemsPayload($_POST['items'] ?? []),
        ];
    }

    private function itemsPayload(mixed $rawItems): array
    {
        if (!is_array($rawItems)) {
            return [];
        }

        $items = [];

        foreach ($rawItems as $item) {
            if (!is_array($item)) {
                continue;
            }

            $items[] = [
                'product_id' => $item['product_id'] ?? null,
                'quantite' => $item['quantite'] ?? null,
                'prix_achat_facture' => $item['prix_achat_facture'] ?? null,
            ];
        }

        return $items;
    }

    private function validateArrival(array $data): array
    {
        $validator = Validator::make($data)
            ->required('supplier_id', 'Fournisseur')
            ->integerPositiveOrZero('supplier_id', 'Fournisseur')
            ->required('numero_arrivage', 'Numero d arrivage')
            ->maxLength('numero_arrivage', 50, 'Numero d arrivage');

        $errors = $validator->errors();

        if ((int) ($data['supplier_id'] ?? 0) <= 0) {
            $errors['supplier_id'][] = 'Fournisseur invalide.';
        }

        if ($data['items'] === []) {
            $errors['items'][] = 'Ajoutez au moins un produit a l arrivage.';
            return $errors;
        }

        foreach ($data['items'] as $index => $item) {
            $line = $index + 1;

            if ((int) ($item['product_id'] ?? 0) <= 0) {
                $errors["items.{$index}.product_id"][] = "Produit invalide a la ligne {$line}.";
            }

            if ((int) ($item['quantite'] ?? 0) <= 0) {
                $errors["items.{$index}.quantite"][] = "Quantite invalide a la ligne {$line}.";
            }

            if (!is_numeric($item['prix_achat_facture'] ?? null) || (float) $item['prix_achat_facture'] < 0) {
                $errors["items.{$index}.prix_achat_facture"][] = "Prix d achat invalide a la ligne {$line}.";
            }
        }

        return $errors;
    }

    private function requireManager(): void
    {
        $this->startSession();

        if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
            $this->redirect('/login');
        }

        $role = strtolower((string) ($_SESSION['user']['role'] ?? $_SESSION['user']['role_legacy'] ?? 'agent'));
        $roleId = (int) ($_SESSION['user']['role_id'] ?? 0);
        $allowed = in_array($role, ['admin', 'gerant', 'super_admin'], true) || in_array($roleId, [1, 2], true);

        if (!$allowed) {
            http_response_code(403);
            $this->flashError('Acces refuse: seuls les administrateurs et gerants valident les arrivages.');
            $this->redirect('/pos');
        }
    }

    private function currentShopId(): int
    {
        $this->startSession();
        $shopId = (int) ($_SESSION['user']['shop_id'] ?? 0);

        if ($shopId <= 0) {
            $this->abort(403, 'Boutique non definie pour cet utilisateur.');
        }

        return $shopId;
    }

    private function currentUserId(): int
    {
        $this->startSession();
        $userId = (int) ($_SESSION['user']['id'] ?? 0);

        if ($userId <= 0) {
            $this->redirect('/login');
        }

        return $userId;
    }

    private function view(string $view, array $data = []): void
    {
        extract($data, EXTR_SKIP);
        $file = dirname(__DIR__) . '/Views/' . $view . '.php';

        if (!is_file($file)) {
            $this->abort(500, 'Vue introuvable.');
        }

        require $file;
    }

    private function flashErrors(array $errors): void
    {
        $this->startSession();
        $_SESSION['errors'] = $errors;
        $_SESSION['old'] = $_POST;
    }

    private function flashSuccess(string $message): void
    {
        $this->startSession();
        $_SESSION['flash_success'] = $message;
    }

    private function flashError(string $message): void
    {
        $this->startSession();
        $_SESSION['flash_error'] = $message;
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

    private function redirect(string $path): never
    {
        header('Location: ' . $path, true, 302);
        exit;
    }

    private function abort(int $statusCode, string $message): never
    {
        http_response_code($statusCode);
        echo $message;
        exit;
    }
}
