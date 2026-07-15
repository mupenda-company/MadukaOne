<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/AppController.php';
require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/SubscriptionGate.php';
require_once dirname(__DIR__) . '/Models/Role.php';
require_once dirname(__DIR__) . '/Models/User.php';

class UserController extends AppController
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
            $shopId = $this->currentShopId();

            if ($nom === '' || $prenom === '' || $roleId < 1 || $shopId < 1) {
                $this->flashError('Veuillez renseigner le prenom, le nom et le role.');
                $this->redirect('/users/create');
            }

            if (!$this->roles->isAssignableInShop($roleId)) {
                $this->flashError('Ce role est reserve a l espace SaaS et ne peut pas etre attribue dans une boutique.');
                $this->redirect('/users/create');
            }

            $limitError = (new SubscriptionGate())->creationError($shopId, 'users');
            if ($limitError !== null) {
                $this->flashError($limitError);
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

        $profile = $this->currentProfile();
        $currentUser = $this->users->sessionPayload($profile);
        $shops = $this->shops();
        $activeShop = $this->activeShop($shops, $currentUser);

        $this->render('users/profile', [
            'pageTitle' => 'Parametres du profil',
            'currentUser' => $currentUser,
            'shops' => $shops,
            'activeShop' => $activeShop,
            'activeMenu' => 'profile',
            'profile' => $profile,
            'hasPassword' => $this->users->hasPassword($profile),
        ]);
    }

    public function updateProfile(array $params = []): void
    {
        $this->startSession();
        $profile = $this->currentProfile();

        try {
            $this->users->updateProfile((int) $profile['id'], $_POST);
            $this->refreshSessionUser((int) $profile['id']);
            $this->flashSuccess('Profil mis a jour.');
        } catch (Throwable $exception) {
            $this->flashError('Mise a jour impossible: ' . $exception->getMessage());
        }

        $this->redirect('/profil');
    }

    public function updatePassword(array $params = []): void
    {
        $this->startSession();
        $profile = $this->currentProfile();
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirmation = (string) ($_POST['password_confirmation'] ?? '');

        try {
            if ($password === '' || strlen($password) < 8) {
                throw new InvalidArgumentException('Le nouveau mot de passe doit contenir au moins 8 caracteres.');
            }

            if ($password !== $passwordConfirmation) {
                throw new InvalidArgumentException('La confirmation du mot de passe ne correspond pas.');
            }

            if ($this->users->hasPassword($profile) && !$this->users->verifyPassword($profile, $currentPassword)) {
                throw new InvalidArgumentException('Le mot de passe actuel est incorrect.');
            }

            $shopId = $this->profileRequiresShopScope($profile) ? $this->currentShopId() : null;
            $this->users->updatePassword((int) $profile['id'], $password, $shopId);
            $this->refreshSessionUser((int) $profile['id']);
            $this->flashSuccess('Mot de passe mis a jour.');
        } catch (Throwable $exception) {
            $this->flashError('Changement de mot de passe impossible: ' . $exception->getMessage());
        }

        $this->redirect('/profil');
    }

    private function employeeRoles(): array
    {
        try {
            $statement = Database::connection()->query('SELECT id, nom FROM roles ORDER BY id ASC');
            $roles = $statement->fetchAll();

            if (is_array($roles) && $roles !== []) {
                return array_values(array_filter(
                    $roles,
                    fn (array $role): bool => !$this->roles->isSaasRoleName((string) ($role['nom'] ?? ''))
                ));
            }
        } catch (Throwable) {
        }

        return [
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

    private function currentProfile(): array
    {
        $userId = (int) ($this->currentUser()['id'] ?? 0);

        if ($userId < 1) {
            $this->flashError('Session utilisateur invalide.');
            $this->redirect('/login');
        }

        $profile = $this->users->findById($userId);

        if ($profile === null) {
            $this->flashError('Profil introuvable.');
            $this->redirect('/logout');
        }

        if ($this->profileRequiresShopScope($profile) && (int) ($profile['shop_id'] ?? 0) !== $this->currentShopId()) {
            $this->flashError('Ce profil ne correspond pas a la boutique active.');
            $this->redirect('/dashboard');
        }

        return $profile;
    }

    private function profileRequiresShopScope(array $profile): bool
    {
        return !$this->users->isSaasAdminUser($profile);
    }

    private function refreshSessionUser(int $userId): void
    {
        $freshUser = $this->users->findById($userId);

        if ($freshUser !== null) {
            $_SESSION['user'] = $this->users->sessionPayload($freshUser);
        }
    }

}
