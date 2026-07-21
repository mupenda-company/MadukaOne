<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/ShopSettings.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/PublicPlan.php';

final class StoreRegistration
{
    public function activePlans(): array
    {
        return (new PublicPlan())->activeWithFeatures();
    }

    public function findActivePlan(int $id): ?array
    {
        if ($id < 1) {
            return null;
        }

        foreach ($this->activePlans() as $plan) {
            if ((int) ($plan['id'] ?? 0) === $id) {
                return $plan;
            }
        }

        return null;
    }

    public function trialDays(): int
    {
        $statement = Database::connection()->prepare('SELECT setting_value FROM saas_settings WHERE setting_key = :key LIMIT 1');
        $statement->execute(['key' => 'default_trial_days']);
        $value = $statement->fetchColumn();

        return max(0, min(3650, is_numeric($value) ? (int) $value : 0));
    }

    public function register(array $data, ?int $planId): array
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();

        try {
            $planWasSelected = $planId !== null;
            $plan = $planWasSelected ? $this->findActivePlan($planId) : null;

            if ($plan === null) {
                throw new InvalidArgumentException('Le forfait sélectionné n’est plus disponible.');
            }

            $email = strtolower(trim((string) $data['email']));
            $emailCheck = $pdo->prepare('SELECT COUNT(*) FROM users WHERE email = :email');
            $emailCheck->execute(['email' => $email]);

            if ((int) $emailCheck->fetchColumn() > 0) {
                throw new DomainException('Un compte existe déjà avec cette adresse email.');
            }

            $categoryId = $this->defaultCategoryId($pdo);
            $slug = $this->uniqueSlug($pdo, (string) $data['shop_name']);
            $shop = $pdo->prepare(
                'INSERT INTO shops (category_id, nom, slug, email, devise_principale, taux_change_cdf, actif)
                 VALUES (:category_id, :nom, :slug, :email, "USD", 2800.0000, 1)'
            );
            $shop->execute([
                'category_id' => $categoryId,
                'nom' => trim((string) $data['shop_name']),
                'slug' => $slug,
                'email' => $email,
            ]);
            $shopId = (int) $pdo->lastInsertId();
            $roleId = $this->ownerRoleId($pdo);
            $users = new User();
            $userId = $users->create([
                'nom' => trim((string) $data['name']),
                'email' => $email,
                'password' => (string) $data['password'],
                'shop_id' => $shopId,
                'role_id' => $roleId,
                'role_legacy' => 'admin',
                'auth_provider' => 'local',
                'actif' => 1,
            ], $shopId);

            $owner = $pdo->prepare('UPDATE shops SET owner_user_id = :user_id WHERE id = :shop_id');
            $owner->execute(['user_id' => $userId, 'shop_id' => $shopId]);

            $trialDays = $this->trialDays();
            $trialStart = new DateTimeImmutable('today');
            $trialEnd = $trialStart->modify('+' . $trialDays . ' days');
            $subscription = $pdo->prepare(
                'INSERT INTO saas_subscriptions
                    (shop_id, plan_id, statut, date_debut, date_fin, renouvellement_auto, notes)
                 VALUES
                    (:shop_id, :plan_id, "trial", :date_debut, :date_fin, 0, :notes)'
            );
            $subscription->execute([
                'shop_id' => $shopId,
                'plan_id' => $plan['id'] ?? null,
                'date_debut' => $trialStart->format('Y-m-d'),
                'date_fin' => $trialEnd->format('Y-m-d'),
                'notes' => $plan === null
                    ? 'Essai gratuit créé lors de l inscription publique.'
                    : 'Essai gratuit associé au forfait ' . (string) $plan['nom'] . ($planWasSelected ? '.' : ' par défaut.'),
            ]);

            $category = $pdo->prepare(
                'INSERT INTO product_categories (shop_id, nom, slug, actif)
                 VALUES (:shop_id, "Général", "general", 1)'
            );
            $category->execute(['shop_id' => $shopId]);
            $this->seedBusinessSettings($pdo, $shopId);

            $user = $users->findById($userId, $shopId, false);

            if ($user === null) {
                throw new RuntimeException('Le compte créé ne peut pas être chargé.');
            }

            $pdo->commit();

            return [
                'user' => $user,
                'shop' => ['id' => $shopId, 'nom' => trim((string) $data['shop_name']), 'slug' => $slug],
                'plan' => $plan,
                'trial_days' => $trialDays,
            ];
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
    }

    private function uniqueSlug(PDO $pdo, string $name): string
    {
        $base = $this->slugify($name);
        $candidate = $base;
        $suffix = 2;
        $statement = $pdo->prepare('SELECT COUNT(*) FROM shops WHERE slug = :slug');

        while (true) {
            $statement->execute(['slug' => $candidate]);

            if ((int) $statement->fetchColumn() === 0) {
                return $candidate;
            }

            $candidate = substr($base, 0, 150) . '-' . $suffix;
            $suffix++;
        }
    }

    private function slugify(string $value): string
    {
        $value = trim($value);
        $ascii = function_exists('iconv') ? iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) : $value;
        $value = strtolower(is_string($ascii) ? $ascii : $value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        if (in_array($value, $this->reservedSlugs(), true)) {
            $value = 'boutique-' . $value;
        }

        return substr($value !== '' ? $value : 'boutique', 0, 150);
    }

    private function reservedSlugs(): array
    {
        return [
            'home', 'accueil', 'privacy', 'confidentialite', 'terms', 'conditions', 'pricing',
            'register-store', 'dashboard', 'login', 'logout', 'activate', 'auth', 'saas-admin',
            'shops', 'boutiques', 'products', 'pos', 'caisse', 'sales', 'ventes', 'customers',
            'clients', 'roles', 'users', 'admin', 'supplies', 'suppliers', 'fournisseurs', 'stock',
            'expenses', 'finances', 'reports', 'rapports', 'pharmacy', 'pharmacie', 'fashion',
            'vetements', 'profil', 'profile', 'backup',
        ];
    }

    private function defaultCategoryId(PDO $pdo): ?int
    {
        $id = $pdo->query("SELECT id FROM shop_categories WHERE slug = 'boutiques' AND actif = 1 LIMIT 1")->fetchColumn();

        return $id !== false ? (int) $id : null;
    }

    private function ownerRoleId(PDO $pdo): int
    {
        $id = $pdo->query("SELECT id FROM roles WHERE LOWER(nom) IN ('proprietaire', 'propriétaire') ORDER BY id ASC LIMIT 1")->fetchColumn();

        if ($id !== false) {
            return (int) $id;
        }

        $statement = $pdo->prepare('INSERT INTO roles (nom, permissions) VALUES (:nom, :permissions)');
        $statement->execute([
            'nom' => 'Propriétaire',
            'permissions' => json_encode(['all' => true], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);

        return (int) $pdo->lastInsertId();
    }

    private function seedBusinessSettings(PDO $pdo, int $shopId): void
    {
        $statement = $pdo->prepare(
            'INSERT INTO shop_business_settings (shop_id, setting_key, setting_value, value_type)
             VALUES (:shop_id, :setting_key, :setting_value, "boolean")'
        );

        foreach (ShopSettings::DEFAULTS as $key => $value) {
            $statement->execute([
                'shop_id' => $shopId,
                'setting_key' => $key,
                'setting_value' => $value,
            ]);
        }
    }
}
