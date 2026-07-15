<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseSaasAdminController.php';
require_once dirname(__DIR__, 2) . '/Models/User.php';

final class SaasProfileController extends BaseSaasAdminController
{
    private User $users;

    public function __construct()
    {
        parent::__construct();
        $this->users = new User();
    }

    public function index(array $params = []): void
    {
        $profile = $this->currentProfile();

        $this->renderSaas('profile/index', [
            'pageTitle' => 'Profil SaaS',
            'activeMenu' => 'saas-profile',
            'profile' => $profile,
            'hasPassword' => $this->users->hasPassword($profile),
        ]);
    }

    public function update(array $params = []): void
    {
        $profile = $this->currentProfile();

        try {
            $this->users->updateProfile((int) $profile['id'], $_POST);
            $this->refreshSessionUser((int) $profile['id']);
            $this->flashSuccess('Profil SaaS mis a jour.');
        } catch (Throwable $exception) {
            $this->flashError('Mise a jour impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/profil');
    }

    public function updatePassword(array $params = []): void
    {
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

            $this->users->updatePassword((int) $profile['id'], $password);
            $this->refreshSessionUser((int) $profile['id']);
            $this->flashSuccess('Mot de passe mis a jour.');
        } catch (Throwable $exception) {
            $this->flashError('Changement de mot de passe impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/profil');
    }

    private function currentProfile(): array
    {
        $userId = (int) ($_SESSION['user']['id'] ?? 0);
        $profile = $this->users->findById($userId);

        if ($profile === null) {
            $this->flashError('Profil introuvable.');
            $this->redirect('/logout');
        }

        return $profile;
    }

    private function refreshSessionUser(int $userId): void
    {
        $freshUser = $this->users->findById($userId);

        if ($freshUser !== null) {
            $_SESSION['user'] = $this->users->sessionPayload($freshUser);
        }
    }
}
