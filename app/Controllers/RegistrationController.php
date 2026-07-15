<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Models/StoreRegistration.php';
require_once dirname(__DIR__) . '/Models/User.php';

final class RegistrationController
{
    private StoreRegistration $registrations;

    public function __construct()
    {
        $this->registrations = new StoreRegistration();
    }

    public function create(array $params = []): void
    {
        $this->startSession();
        $selectedPlan = $this->selectedPlan();
        $flashError = $_SESSION['flash_error'] ?? null;
        $old = is_array($_SESSION['registration_old'] ?? null) ? $_SESSION['registration_old'] : [];
        unset($_SESSION['flash_error'], $_SESSION['registration_old']);
        $csrfToken = $this->csrfToken();
        $basePath = $this->basePath();

        require dirname(__DIR__) . '/Views/auth/register-store.php';
    }

    public function store(array $params = []): void
    {
        $this->startSession();
        $data = [
            'name' => trim((string) ($_POST['name'] ?? '')),
            'email' => strtolower(trim((string) ($_POST['email'] ?? ''))),
            'password' => (string) ($_POST['password'] ?? ''),
            'password_confirmation' => (string) ($_POST['password_confirmation'] ?? ''),
            'shop_name' => trim((string) ($_POST['shop_name'] ?? '')),
        ];

        $error = $this->validationError($data, (string) ($_POST['_token'] ?? ''));

        if ($error !== null) {
            $_SESSION['flash_error'] = $error;
            $_SESSION['registration_old'] = [
                'name' => $data['name'],
                'email' => $data['email'],
                'shop_name' => $data['shop_name'],
            ];
            $this->redirect('/register-store');
        }

        try {
            $planId = isset($_SESSION['selected_plan']) ? (int) $_SESSION['selected_plan'] : null;
            $result = $this->registrations->register($data, $planId !== null && $planId > 0 ? $planId : null);
            $users = new User();

            session_regenerate_id(true);
            $_SESSION['user'] = $users->sessionPayload($result['user']);
            $_SESSION['shop_id'] = (int) $result['shop']['id'];
            $_SESSION['current_shop_id'] = (int) $result['shop']['id'];
            $_SESSION['flash_success'] = 'Bienvenue ! Votre boutique est prête et votre essai gratuit de 14 jours a commencé.';
            unset($_SESSION['selected_plan'], $_SESSION['registration_old']);

            $this->redirect('/dashboard');
        } catch (DomainException|InvalidArgumentException $exception) {
            $_SESSION['flash_error'] = $exception->getMessage();
            $_SESSION['registration_old'] = [
                'name' => $data['name'],
                'email' => $data['email'],
                'shop_name' => $data['shop_name'],
            ];
            $this->redirect('/register-store');
        } catch (Throwable) {
            $_SESSION['flash_error'] = 'La création de la boutique a échoué. Veuillez réessayer.';
            $_SESSION['registration_old'] = [
                'name' => $data['name'],
                'email' => $data['email'],
                'shop_name' => $data['shop_name'],
            ];
            $this->redirect('/register-store');
        }
    }

    private function selectedPlan(): ?array
    {
        $planId = (int) ($_SESSION['selected_plan'] ?? 0);

        return $planId > 0 ? $this->registrations->findActivePlan($planId) : null;
    }

    private function validationError(array $data, string $token): ?string
    {
        if (!$this->validCsrf($token)) {
            return 'La page a expiré. Veuillez recommencer.';
        }

        if (mb_strlen($data['name']) < 2 || mb_strlen($data['name']) > 120) {
            return 'Le nom doit contenir entre 2 et 120 caractères.';
        }

        if (filter_var($data['email'], FILTER_VALIDATE_EMAIL) === false || mb_strlen($data['email']) > 190) {
            return 'Veuillez saisir une adresse email valide.';
        }

        if (strlen($data['password']) < 8) {
            return 'Le mot de passe doit contenir au moins 8 caractères.';
        }

        if ($data['password'] !== $data['password_confirmation']) {
            return 'La confirmation du mot de passe ne correspond pas.';
        }

        if (mb_strlen($data['shop_name']) < 2 || mb_strlen($data['shop_name']) > 120) {
            return 'Le nom de la boutique doit contenir entre 2 et 120 caractères.';
        }

        return null;
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
