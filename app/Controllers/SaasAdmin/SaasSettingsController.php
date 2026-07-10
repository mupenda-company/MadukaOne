<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseSaasAdminController.php';

final class SaasSettingsController extends BaseSaasAdminController
{
    public function index(array $params = []): void
    {
        $this->renderSaas('settings/index', [
            'pageTitle' => 'Parametres generaux',
            'activeMenu' => 'saas-settings',
            'settings' => $this->repo->settingsMap(),
        ]);
    }

    public function update(array $params = []): void
    {
        try {
            $payload = is_array($_POST['settings'] ?? null) ? $_POST['settings'] : [];
            $this->repo->updateSettings($payload);
            $this->flashSuccess('Parametres SaaS mis a jour.');
        } catch (Throwable $exception) {
            $this->flashError('Mise a jour impossible: ' . $exception->getMessage());
        }

        $this->redirect('/saas-admin/parametres');
    }
}
