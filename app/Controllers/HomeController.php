<?php

declare(strict_types=1);

final class HomeController
{
    public function index(array $params = []): void
    {
        $this->renderPublic('public/home', [
            'pageTitle' => 'Accueil',
            'pageDescription' => 'MadukaOne centralise la caisse, le stock, les ventes, les clients et les rapports pour piloter une boutique moderne.',
            'activePublicPage' => 'home',
        ]);
    }

    public function privacy(array $params = []): void
    {
        $this->renderPublic('public/privacy', [
            'pageTitle' => 'Politique de confidentialité',
            'pageDescription' => 'Politique de confidentialité de MadukaOne pour la gestion des données professionnelles.',
            'activePublicPage' => 'privacy',
        ]);
    }

    public function terms(array $params = []): void
    {
        $this->renderPublic('public/terms', [
            'pageTitle' => 'Conditions d’utilisation',
            'pageDescription' => 'Conditions d’utilisation de MadukaOne pour les administrateurs, gérants et agents.',
            'activePublicPage' => 'terms',
        ]);
    }

    private function renderPublic(string $view, array $data = []): void
    {
        if (!headers_sent()) {
            header('Content-Type: text/html; charset=UTF-8');
        }

        $appConfig = require dirname(__DIR__, 2) . '/config/app.php';
        $appName = (string) ($appConfig['name'] ?? 'MadukaOne');
        $pageTitle = (string) ($data['pageTitle'] ?? $appName);
        $pageDescription = (string) ($data['pageDescription'] ?? '');
        $activePublicPage = (string) ($data['activePublicPage'] ?? '');

        $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        $basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;

        $asset = static function (string $path) use ($basePath): string {
            return htmlspecialchars($basePath . '/' . ltrim($path, '/'), ENT_QUOTES, 'UTF-8');
        };

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

        extract($data, EXTR_SKIP);

        ob_start();
        require dirname(__DIR__) . '/Views/' . $view . '.php';
        $content = (string) ob_get_clean();

        require dirname(__DIR__) . '/Views/layouts/public.php';
    }
}
