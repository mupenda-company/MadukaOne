<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Models/SaasAdmin/SaasAdminRepository.php';

abstract class BaseSaasAdminController
{
    protected SaasAdminRepository $repo;

    public function __construct()
    {
        $this->startSession();
        $this->repo = new SaasAdminRepository();
        $this->repo->ensureSchema();
    }

    protected function renderSaas(string $view, array $data = []): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        $basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;
        $asset = static fn (string $path): string => htmlspecialchars($basePath . '/' . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
        $url = static function (string $path, array $query = []) use ($basePath): string {
            $href = $basePath . '/' . ltrim($path, '/');

            if ($path === '/') {
                $href = $basePath === '' ? '/' : $basePath . '/';
            }

            if ($query !== []) {
                $href .= (str_contains($href, '?') ? '&' : '?') . http_build_query($query);
            }

            return htmlspecialchars($href, ENT_QUOTES, 'UTF-8');
        };

        $currentUser = $_SESSION['user'] ?? ['nom' => 'Super Admin', 'email' => 'admin@example.com', 'role' => 'super_admin'];
        $activeMenu = (string) ($data['activeMenu'] ?? 'saas-dashboard');
        $pageTitle = (string) ($data['pageTitle'] ?? 'Administration SaaS');

        extract($data, EXTR_SKIP);

        ob_start();
        require dirname(__DIR__, 2) . '/Views/saas-admin/' . $view . '.php';
        $content = (string) ob_get_clean();

        require dirname(__DIR__, 2) . '/Views/saas-admin/layouts/saas.php';
    }

    protected function redirect(string $path): never
    {
        $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        $basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;
        header('Location: ' . $basePath . '/' . ltrim($path, '/'), true, 302);
        exit;
    }

    protected function flashSuccess(string $message): void
    {
        $_SESSION['flash_success'] = $message;
    }

    protected function flashError(string $message): void
    {
        $_SESSION['flash_error'] = $message;
    }

    protected function validateShopPayload(array $data): ?string
    {
        if (trim((string) ($data['nom'] ?? '')) === '') {
            return 'Le nom de la boutique est obligatoire.';
        }

        if ((int) ($data['category_id'] ?? 0) < 1) {
            return 'La categorie de la boutique est obligatoire.';
        }

        if (!in_array(($data['devise_principale'] ?? ''), ['USD', 'CDF'], true)) {
            return 'La devise principale doit etre USD ou CDF.';
        }

        if (!is_numeric($data['taux_change_cdf'] ?? null) || (float) $data['taux_change_cdf'] <= 0) {
            return 'Le taux de change CDF doit etre superieur a zero.';
        }

        $email = trim((string) ($data['email'] ?? ''));

        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return 'L adresse email est invalide.';
        }

        return null;
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
