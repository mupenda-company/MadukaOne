<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Models/Role.php';
require_once dirname(__DIR__) . '/Models/User.php';

class UserController
{
    private Role $roles;
    private User $users;

    public function __construct()
    {
        $this->roles = new Role();
        $this->users = new User();
    }

    public function index(array $params = []): void
    {
        $this->startSession();

        $currentUser = $this->currentUser();
        $shops = $this->shops();
        $activeShop = $this->activeShop($shops, $currentUser);
        $users = $this->users->allByShop($this->currentShopId(), false);

        $this->render('users/index', [
            'pageTitle' => 'Utilisateurs',
            'currentUser' => $currentUser,
            'shops' => $shops,
            'activeShop' => $activeShop,
            'activeMenu' => 'users',
            'users' => $users,
            'userStats' => $this->userStats($users),
        ]);
    }

    public function create(array $params = []): void
    {
        $this->startSession();

        $currentUser = $this->currentUser();
        $shops = $this->shops();
        $activeShop = $this->activeShop($shops, $currentUser);

        $this->render('admin/users/create', [
            'pageTitle' => 'Nouvel employe',
            'currentUser' => $currentUser,
            'shops' => $shops,
            'activeShop' => $activeShop,
            'activeMenu' => 'users',
            'roles' => $this->employeeRoles(),
        ]);
    }

    public function store(array $params = []): void
    {
        $this->startSession();

        try {
            $nom = trim((string) ($_POST['nom'] ?? ''));
            $prenom = trim((string) ($_POST['prenom'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $telephone = trim((string) ($_POST['telephone'] ?? ''));
            $roleId = (int) ($_POST['role_id'] ?? 0);
            $shopId = (int) ($_POST['shop_id'] ?? 0);

            if ($shopId < 1) {
                $shopId = $this->currentShopId();
            }

            if ($nom === '' || $prenom === '' || $roleId < 1 || $shopId < 1) {
                $this->flashError('Veuillez renseigner le prenom, le nom et le role.');
                $this->redirect('/users/create');
            }

            if (!$this->roles->isAssignableInShop($roleId)) {
                $this->flashError('Ce role est reserve a l espace SaaS et ne peut pas etre attribue dans une boutique.');
                $this->redirect('/users/create');
            }

            $invitationCode = $this->generateInvitationCode();
            $created = $this->users->createWithInvitation([
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $telephone,
                'role_id' => $roleId,
                'shop_id' => $shopId,
                'invitation_code' => $invitationCode,
            ]);

            if (!$created) {
                throw new RuntimeException('Insertion employe echouee.');
            }

            $_SESSION['flash']['success_code'] = $invitationCode;
            $this->redirect('/users/create');
        } catch (Throwable) {
            $this->flashError('Creation impossible. Verifiez les informations puis reessayez.');
            $this->redirect('/users/create');
        }
    }

    public function edit(array $params = []): void
    {
        $this->startSession();

        $id = (int) ($params['id'] ?? 0);
        $shopId = $this->currentShopId();
        $user = $this->users->findByIdAndShop($id, $shopId);

        if ($user === null) {
            $this->flashError('Utilisateur introuvable pour cette boutique.');
            $this->redirect('/users');
        }

        $currentUser = $this->currentUser();
        $shops = $this->shops();
        $activeShop = $this->activeShop($shops, $currentUser);

        $this->render('admin/users/edit', [
            'pageTitle' => 'Modifier un employe',
            'currentUser' => $currentUser,
            'shops' => $shops,
            'activeShop' => $activeShop,
            'activeMenu' => 'users',
            'roles' => $this->employeeRoles(),
            'user' => $user,
        ]);
    }

    public function update(array $params = []): void
    {
        $this->startSession();

        $id = (int) ($params['id'] ?? 0);
        $shopId = $this->currentShopId();

        try {
            $user = $this->users->findByIdAndShop($id, $shopId);

            if ($user === null) {
                $this->flashError('Utilisateur introuvable pour cette boutique.');
                $this->redirect('/users');
            }

            $nom = trim((string) ($_POST['nom'] ?? ''));
            $prenom = trim((string) ($_POST['prenom'] ?? ''));
            $email = trim((string) ($_POST['email'] ?? ''));
            $telephone = trim((string) ($_POST['telephone'] ?? ''));
            $roleId = (int) ($_POST['role_id'] ?? 0);
            $targetShopId = (int) ($_POST['shop_id'] ?? 0);

            if ($nom === '' || $prenom === '' || $roleId < 1 || $targetShopId < 1) {
                $this->flashError('Veuillez renseigner le prenom, le nom, la boutique et le role.');
                $this->redirect('/admin/users/edit/' . $id);
            }

            if (!$this->roles->isAssignableInShop($roleId)) {
                $this->flashError('Ce role est reserve a l espace SaaS et ne peut pas etre attribue dans une boutique.');
                $this->redirect('/admin/users/edit/' . $id);
            }

            $this->users->updateUser($id, [
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email,
                'telephone' => $telephone,
                'shop_id' => $targetShopId,
                'role_id' => $roleId,
            ]);

            $this->flashSuccess('Utilisateur modifie avec succes.');
            $this->redirect('/users');
        } catch (Throwable) {
            $this->flashError('Modification impossible. Verifiez les informations puis reessayez.');
            $this->redirect('/admin/users/edit/' . $id);
        }
    }

    public function delete(array $params = []): void
    {
        $this->startSession();

        $id = (int) ($params['id'] ?? 0);
        $shopId = $this->currentShopId();

        try {
            $user = $this->users->findByIdAndShop($id, $shopId);

            if ($user === null) {
                $this->flashError('Utilisateur introuvable pour cette boutique.');
                $this->redirect('/users');
            }

            if ($id === (int) ($_SESSION['user']['id'] ?? 0)) {
                $this->flashError('Vous ne pouvez pas supprimer votre propre compte pendant que vous etes connecte.');
                $this->redirect('/users');
            }

            if (!$this->users->deleteUser($id)) {
                throw new RuntimeException('Suppression utilisateur echouee.');
            }

            $this->flashSuccess('Utilisateur supprime avec succes.');
            $this->redirect('/users');
        } catch (Throwable) {
            $this->flashError('Suppression impossible. Cet utilisateur est peut-etre deja lie a des operations.');
            $this->redirect('/users');
        }
    }

    public function deactivate(array $params = []): void
    {
        $this->startSession();

        $id = (int) ($params['id'] ?? 0);
        $shopId = $this->currentShopId();

        try {
            $user = $this->users->findByIdAndShop($id, $shopId);

            if ($user === null) {
                $this->flashError('Utilisateur introuvable pour cette boutique.');
                $this->redirect('/users');
            }

            if ($id === (int) ($_SESSION['user']['id'] ?? 0)) {
                $this->flashError('Vous ne pouvez pas desactiver votre propre compte pendant que vous etes connecte.');
                $this->redirect('/users');
            }

            if (!$this->users->deactivateUser($id)) {
                throw new RuntimeException('Desactivation utilisateur echouee.');
            }

            $this->flashSuccess('Utilisateur desactive avec succes.');
            $this->redirect('/users');
        } catch (Throwable) {
            $this->flashError('Desactivation impossible. Veuillez reessayer.');
            $this->redirect('/users');
        }
    }

    public function activate(array $params = []): void
    {
        $this->startSession();

        $id = (int) ($params['id'] ?? 0);
        $shopId = $this->currentShopId();

        try {
            $user = $this->users->findByIdAndShop($id, $shopId);

            if ($user === null) {
                $this->flashError('Utilisateur introuvable pour cette boutique.');
                $this->redirect('/users');
            }

            if (!$this->users->activateUser($id)) {
                throw new RuntimeException('Activation utilisateur echouee.');
            }

            $this->flashSuccess('Utilisateur active avec succes.');
            $this->redirect('/users');
        } catch (Throwable) {
            $this->flashError('Activation impossible. Veuillez reessayer.');
            $this->redirect('/users');
        }
    }

    public function profile(array $params = []): void
    {
        $this->startSession();

        $currentUser = $this->currentUser();
        $shops = $this->shops();
        $activeShop = $this->activeShop($shops, $currentUser);

        $this->render('users/profile', [
            'pageTitle' => 'Parametres du profil',
            'currentUser' => $currentUser,
            'shops' => $shops,
            'activeShop' => $activeShop,
            'activeMenu' => 'profile',
        ]);
    }

    private function render(string $view, array $data = []): void
    {
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

        require dirname(__DIR__) . '/Views/layouts/app.php';
    }

    private function employeeRoles(): array
    {
        try {
            $statement = Database::connection()->query('SELECT id, nom FROM roles ORDER BY id ASC');
            $roles = $statement->fetchAll();

            if (is_array($roles) && $roles !== []) {
                return $roles;
            }
        } catch (Throwable) {
        }

        return [
            ['id' => 1, 'nom' => 'Super Admin'],
            ['id' => 2, 'nom' => 'Gerant'],
            ['id' => 3, 'nom' => 'Caissier'],
        ];
    }

    private function generateInvitationCode(): string
    {
        do {
            $code = 'MADUKA-' . strtoupper(bin2hex(random_bytes(3)));
            $exists = $this->users->verifyInvitationCode($code) !== null;
        } while ($exists);

        return $code;
    }

    private function flashError(string $message): void
    {
        $_SESSION['flash_error'] = $message;
    }

    private function flashSuccess(string $message): void
    {
        $_SESSION['flash_success'] = $message;
    }

    private function currentShopId(): int
    {
        $shopId = (int) ($_SESSION['shop_id'] ?? 0);

        if ($shopId > 0) {
            return $shopId;
        }

        $shopId = (int) ($_SESSION['user']['shop_id'] ?? 0);

        if ($shopId > 0) {
            return $shopId;
        }

        return (int) ($_SESSION['current_shop_id'] ?? 1);
    }

    private function redirect(string $path): never
    {
        $basePath = rtrim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? ''))), '/');
        $basePath = ($basePath === '' || $basePath === '.') ? '' : $basePath;

        header('Location: ' . $basePath . '/' . ltrim($path, '/'), true, 302);
        exit;
    }

    private function currentUser(): array
    {
        $user = $_SESSION['user'] ?? null;

        if (is_array($user)) {
            return $user;
        }

        return [
            'id' => null,
            'nom' => 'Administrateur',
            'email' => 'admin@example.com',
            'role' => 'admin',
            'shop_id' => 1,
            'auth_provider' => 'local',
        ];
    }

    private function shops(): array
    {
        try {
            $statement = Database::connection()->query(
                'SELECT id, nom, adresse, telephone, actif FROM shops WHERE actif = 1 ORDER BY nom ASC'
            );
            $shops = $statement->fetchAll();

            if (is_array($shops) && $shops !== []) {
                return $shops;
            }
        } catch (Throwable) {
        }

        return [
            [
                'id' => 1,
                'nom' => 'Boutique Pilote - Centre Ville',
                'adresse' => 'Av. Principale No 10',
                'telephone' => '+243000000000',
                'actif' => 1,
            ],
        ];
    }

    private function activeShop(array $shops, array $currentUser): array
    {
        $requestedShopId = filter_input(INPUT_GET, 'shop_id', FILTER_VALIDATE_INT);
        $sessionShopId = isset($_SESSION['current_shop_id']) ? (int) $_SESSION['current_shop_id'] : null;
        $preferredShopId = $requestedShopId ?: $sessionShopId ?: (int) ($currentUser['shop_id'] ?? 0);

        foreach ($shops as $shop) {
            if ((int) $shop['id'] === $preferredShopId) {
                $_SESSION['current_shop_id'] = (int) $shop['id'];
                return $shop;
            }
        }

        $_SESSION['current_shop_id'] = (int) $shops[0]['id'];

        return $shops[0];
    }

    private function userStats(array $users): array
    {
        $stats = [
            'total' => count($users),
            'active' => 0,
            'inactive' => 0,
            'oauth' => 0,
            'shops' => [],
        ];

        foreach ($users as $user) {
            if ((int) ($user['actif'] ?? 0) === 1) {
                $stats['active']++;
            } else {
                $stats['inactive']++;
            }

            if (in_array((string) ($user['auth_provider'] ?? 'local'), ['google', 'apple'], true)) {
                $stats['oauth']++;
            }

            $shopName = trim((string) ($user['shop_name'] ?? ''));
            if ($shopName !== '') {
                $stats['shops'][$shopName] = true;
            }
        }

        $stats['shops'] = count($stats['shops']);

        return $stats;
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
