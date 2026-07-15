<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Models/StoreRegistration.php';

final class PricingController
{
    private StoreRegistration $registrations;

    public function __construct()
    {
        $this->registrations = new StoreRegistration();
    }

    public function index(array $params = []): void
    {
        $this->startSession();
        $plans = $this->registrations->activePlans();
        $selectedPlanId = (int) ($_SESSION['selected_plan'] ?? 0);
        $csrfToken = $this->csrfToken();
        $basePath = $this->basePath();

        require dirname(__DIR__) . '/Views/public/pricing.php';
    }

    public function select(array $params = []): void
    {
        $this->startSession();

        if (!$this->validCsrf((string) ($_POST['_token'] ?? ''))) {
            $_SESSION['flash_error'] = 'La page a expiré. Veuillez sélectionner le forfait à nouveau.';
            $this->redirect('/pricing');
        }

        $planId = filter_var($_POST['plan_id'] ?? null, FILTER_VALIDATE_INT);
        $plan = is_int($planId) ? $this->registrations->findActivePlan($planId) : null;

        if ($plan === null) {
            $_SESSION['flash_error'] = 'Le forfait sélectionné est invalide ou indisponible.';
            $this->redirect('/pricing');
        }

        $_SESSION['selected_plan'] = (int) $plan['id'];
        $this->redirect('/register-store');
    }

    private function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    private function validCsrf(string $token): bool
    {
        return isset($_SESSION['csrf_token'])
            && is_string($_SESSION['csrf_token'])
            && $token !== ''
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    private function startSession(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start(['cookie_httponly' => true, 'cookie_samesite' => 'Lax', 'use_strict_mode' => true]);
        }
    }

    private function basePath(): string
    {
        $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');

        return ($basePath === '' || $basePath === '.') ? '' : $basePath;
    }

    private function redirect(string $path): never
    {
        header('Location: ' . $this->basePath() . '/' . ltrim($path, '/'), true, 302);
        exit;
    }
}
