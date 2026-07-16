<?php

declare(strict_types=1);

require_once __DIR__ . '/BaseSaasAdminController.php';
require_once dirname(__DIR__, 2) . '/Models/LegalContent.php';

final class SaasLegalController extends BaseSaasAdminController
{
    private LegalContent $legal;

    public function __construct()
    {
        parent::__construct();
        $this->legal = new LegalContent();
    }

    public function privacy(array $params = []): void { $this->index('privacy'); }
    public function terms(array $params = []): void { $this->index('terms'); }
    public function storePrivacy(array $params = []): void { $this->store('privacy'); }
    public function storeTerms(array $params = []): void { $this->store('terms'); }
    public function updatePrivacy(array $params = []): void { $this->update('privacy', (int) ($params['id'] ?? 0)); }
    public function updateTerms(array $params = []): void { $this->update('terms', (int) ($params['id'] ?? 0)); }
    public function togglePrivacy(array $params = []): void { $this->toggle('privacy', (int) ($params['id'] ?? 0)); }
    public function toggleTerms(array $params = []): void { $this->toggle('terms', (int) ($params['id'] ?? 0)); }
    public function deletePrivacy(array $params = []): void { $this->delete('privacy', (int) ($params['id'] ?? 0)); }
    public function deleteTerms(array $params = []): void { $this->delete('terms', (int) ($params['id'] ?? 0)); }

    private function index(string $type): void
    {
        $privacy = $type === 'privacy';
        $this->renderSaas('legal/index', [
            'pageTitle' => $privacy ? 'Politique de confidentialité' : 'Conditions d’utilisation',
            'activeMenu' => $privacy ? 'saas-privacy' : 'saas-terms',
            'legalType' => $type,
            'sections' => $this->legal->sections($type),
        ]);
    }

    private function store(string $type): never
    {
        try {
            $this->legal->create($type, $_POST);
            $this->flashSuccess('La section a été ajoutée et publiée.');
        } catch (Throwable $exception) {
            $this->flashError('Création impossible : ' . $exception->getMessage());
        }
        $this->redirect($this->path($type));
    }

    private function update(string $type, int $id): never
    {
        try {
            $this->legal->update($type, $id, $_POST);
            $this->flashSuccess('La section a été mise à jour.');
        } catch (Throwable $exception) {
            $this->flashError('Modification impossible : ' . $exception->getMessage());
        }
        $this->redirect($this->path($type));
    }

    private function toggle(string $type, int $id): never
    {
        $this->legal->toggle($type, $id);
        $this->flashSuccess('Le statut de publication a été modifié.');
        $this->redirect($this->path($type));
    }

    private function delete(string $type, int $id): never
    {
        $this->legal->delete($type, $id);
        $this->flashSuccess('La section a été supprimée.');
        $this->redirect($this->path($type));
    }

    private function path(string $type): string
    {
        return $type === 'privacy' ? '/saas-admin/confidentialite' : '/saas-admin/conditions';
    }
}
