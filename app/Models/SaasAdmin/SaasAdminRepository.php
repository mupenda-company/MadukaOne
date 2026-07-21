<?php

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Core/Database.php';
require_once dirname(__DIR__, 2) . '/Core/Model.php';

final class SaasAdminRepository extends Model
{
    private bool $schemaReady = false;

    public function ensureSchema(): void
    {
        if ($this->schemaReady) {
            return;
        }

        $pdo = Database::connection();
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS shop_categories (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(120) NOT NULL,
                slug VARCHAR(140) NOT NULL,
                description TEXT NULL,
                actif TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_shop_categories_slug (slug),
                UNIQUE KEY uq_shop_categories_nom (nom),
                KEY idx_shop_categories_actif (actif)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $this->ensureShopCategoryDescriptionColumn();
        $this->ensureShopCategoryColumn();
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS saas_features (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                code VARCHAR(80) NOT NULL,
                nom VARCHAR(120) NOT NULL,
                description TEXT NULL,
                categorie VARCHAR(80) NOT NULL DEFAULT 'general',
                actif TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_saas_features_code (code),
                KEY idx_saas_features_actif (actif)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS saas_subscription_plans (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                nom VARCHAR(120) NOT NULL,
                code VARCHAR(80) NOT NULL,
                limite_boutiques INT NULL,
                prix_mensuel_usd DECIMAL(12,2) NOT NULL DEFAULT 0.00,
                limite_utilisateurs INT NULL,
                limite_produits INT NULL,
                description TEXT NULL,
                actif TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_saas_subscription_plans_code (code)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $this->ensurePlanShopLimitColumn();
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS saas_subscriptions (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                shop_id BIGINT UNSIGNED NOT NULL,
                plan_id BIGINT UNSIGNED NULL,
                statut ENUM('trial','active','past_due','suspended','cancelled') NOT NULL DEFAULT 'trial',
                date_debut DATE NOT NULL,
                date_fin DATE NULL,
                renouvellement_auto TINYINT(1) NOT NULL DEFAULT 1,
                notes TEXT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_saas_subscriptions_shop (shop_id),
                KEY idx_saas_subscriptions_statut (statut),
                CONSTRAINT fk_saas_subscriptions_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT fk_saas_subscriptions_plan FOREIGN KEY (plan_id) REFERENCES saas_subscription_plans(id) ON UPDATE CASCADE ON DELETE SET NULL
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS saas_shop_features (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                shop_id BIGINT UNSIGNED NOT NULL,
                feature_id BIGINT UNSIGNED NOT NULL,
                actif TINYINT(1) NOT NULL DEFAULT 1,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_saas_shop_features (shop_id, feature_id),
                CONSTRAINT fk_saas_shop_features_shop FOREIGN KEY (shop_id) REFERENCES shops(id) ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT fk_saas_shop_features_feature FOREIGN KEY (feature_id) REFERENCES saas_features(id) ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS saas_category_plans (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                category_id BIGINT UNSIGNED NOT NULL,
                plan_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_saas_category_plans (category_id, plan_id),
                CONSTRAINT fk_saas_category_plans_category FOREIGN KEY (category_id) REFERENCES shop_categories(id) ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT fk_saas_category_plans_plan FOREIGN KEY (plan_id) REFERENCES saas_subscription_plans(id) ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS saas_plan_features (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                plan_id BIGINT UNSIGNED NOT NULL,
                feature_id BIGINT UNSIGNED NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uq_saas_plan_features (plan_id, feature_id),
                CONSTRAINT fk_saas_plan_features_plan FOREIGN KEY (plan_id) REFERENCES saas_subscription_plans(id) ON UPDATE CASCADE ON DELETE CASCADE,
                CONSTRAINT fk_saas_plan_features_feature FOREIGN KEY (feature_id) REFERENCES saas_features(id) ON UPDATE CASCADE ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
        $pdo->exec(
            "CREATE TABLE IF NOT EXISTS saas_settings (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                setting_key VARCHAR(120) NOT NULL,
                setting_value TEXT NULL,
                value_type ENUM('string','integer','decimal','boolean','json') NOT NULL DEFAULT 'string',
                label VARCHAR(160) NOT NULL,
                description TEXT NULL,
                group_name VARCHAR(80) NOT NULL DEFAULT 'general',
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_saas_settings_key (setting_key),
                KEY idx_saas_settings_group (group_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );

        $this->seedDefaults();
        $this->schemaReady = true;
    }

    public function dashboardStats(): array
    {
        $this->ensureSchema();
        $pdo = Database::connection();

        return [
            'shops' => (int) $pdo->query('SELECT COUNT(*) FROM shops')->fetchColumn(),
            'active_shops' => (int) $pdo->query('SELECT COUNT(*) FROM shops WHERE actif = 1')->fetchColumn(),
            'suspended_shops' => (int) $pdo->query('SELECT COUNT(*) FROM shops WHERE actif = 0')->fetchColumn(),
            'users' => (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn(),
            'products' => (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn(),
            'sales' => (int) $pdo->query('SELECT COUNT(*) FROM sales')->fetchColumn(),
            'sales_revenue' => (float) $pdo->query("SELECT COALESCE(SUM(total_montant), 0) FROM sales WHERE statut = 'validee'")->fetchColumn(),
            'plans' => (int) $pdo->query('SELECT COUNT(*) FROM saas_subscription_plans WHERE actif = 1')->fetchColumn(),
            'features' => (int) $pdo->query('SELECT COUNT(*) FROM saas_features WHERE actif = 1')->fetchColumn(),
            'trial_subscriptions' => (int) $pdo->query("SELECT COUNT(*) FROM saas_subscriptions WHERE statut = 'trial'")->fetchColumn(),
            'active_subscriptions' => (int) $pdo->query("SELECT COUNT(*) FROM saas_subscriptions WHERE statut IN ('trial','active')")->fetchColumn(),
            'past_due_subscriptions' => (int) $pdo->query("SELECT COUNT(*) FROM saas_subscriptions WHERE statut = 'past_due'")->fetchColumn(),
            'suspended_subscriptions' => (int) $pdo->query("SELECT COUNT(*) FROM saas_subscriptions WHERE statut IN ('suspended','cancelled')")->fetchColumn(),
            'monthly_revenue' => (float) $pdo->query(
                "SELECT COALESCE(SUM(plans.prix_mensuel_usd), 0)
                 FROM saas_subscriptions subscriptions
                 INNER JOIN saas_subscription_plans plans ON plans.id = subscriptions.plan_id
                 WHERE subscriptions.statut = 'active'"
            )->fetchColumn(),
        ];
    }

    public function dashboardDistributions(): array
    {
        $this->ensureSchema();
        $pdo = Database::connection();

        return [
            'plans' => $pdo->query(
                "SELECT COALESCE(plans.nom, 'Sans plan') AS label, COUNT(shops.id) AS total
                 FROM shops
                 LEFT JOIN saas_subscriptions subscriptions ON subscriptions.shop_id = shops.id
                 LEFT JOIN saas_subscription_plans plans ON plans.id = subscriptions.plan_id
                 GROUP BY COALESCE(plans.nom, 'Sans plan')
                 ORDER BY total DESC, label ASC"
            )->fetchAll(),
            'statuses' => $pdo->query(
                "SELECT COALESCE(subscriptions.statut, 'non_configure') AS label, COUNT(shops.id) AS total
                 FROM shops
                 LEFT JOIN saas_subscriptions subscriptions ON subscriptions.shop_id = shops.id
                 GROUP BY COALESCE(subscriptions.statut, 'non_configure')
                 ORDER BY total DESC, label ASC"
            )->fetchAll(),
            'categories' => $pdo->query(
                "SELECT COALESCE(categories.nom, 'Sans categorie') AS label, COUNT(shops.id) AS total
                 FROM shops
                 LEFT JOIN shop_categories categories ON categories.id = shops.category_id
                 GROUP BY COALESCE(categories.nom, 'Sans categorie')
                 ORDER BY total DESC, label ASC
                 LIMIT 8"
            )->fetchAll(),
        ];
    }

    public function shopsNeedingAttention(): array
    {
        $this->ensureSchema();

        return Database::connection()->query(
            "SELECT shops.nom,
                    shops.actif,
                    COALESCE(subscriptions.statut, 'non_configure') AS subscription_status,
                    subscriptions.date_fin,
                    plans.nom AS plan_name,
                    COUNT(DISTINCT users.id) AS users_count,
                    COUNT(DISTINCT products.id) AS products_count
             FROM shops
             LEFT JOIN users ON users.shop_id = shops.id
             LEFT JOIN products ON products.shop_id = shops.id
             LEFT JOIN saas_subscriptions subscriptions ON subscriptions.shop_id = shops.id
             LEFT JOIN saas_subscription_plans plans ON plans.id = subscriptions.plan_id
             WHERE shops.actif = 0
                OR subscriptions.id IS NULL
                OR subscriptions.statut IN ('past_due', 'suspended', 'cancelled')
                OR (subscriptions.date_fin IS NOT NULL AND subscriptions.date_fin <= DATE_ADD(CURRENT_DATE, INTERVAL 7 DAY))
             GROUP BY shops.id, subscriptions.id, plans.id
             ORDER BY
                CASE
                    WHEN shops.actif = 0 THEN 1
                    WHEN subscriptions.statut IN ('past_due', 'suspended', 'cancelled') THEN 2
                    WHEN subscriptions.id IS NULL THEN 3
                    ELSE 4
                END,
                subscriptions.date_fin ASC,
                shops.nom ASC
             LIMIT 8"
        )->fetchAll();
    }

    public function shopsWithMetrics(): array
    {
        $this->ensureSchema();
        $statement = Database::connection()->query(
            "SELECT
                shops.*,
                categories.nom AS category_name,
                categories.slug AS category_slug,
                COUNT(DISTINCT users.id) AS users_count,
                COUNT(DISTINCT products.id) AS products_count,
                COUNT(DISTINCT sales.id) AS sales_count,
                COALESCE(SUM(CASE WHEN sales.statut = 'validee' THEN sales.total_montant ELSE 0 END), 0) AS sales_total,
                subscriptions.statut AS subscription_status,
                subscriptions.plan_id,
                subscriptions.date_debut,
                subscriptions.date_fin,
                subscriptions.renouvellement_auto,
                subscriptions.notes AS subscription_notes,
                plans.nom AS plan_name,
                plans.prix_mensuel_usd
             FROM shops
             LEFT JOIN shop_categories categories ON categories.id = shops.category_id
             LEFT JOIN users ON users.shop_id = shops.id
             LEFT JOIN products ON products.shop_id = shops.id
             LEFT JOIN sales ON sales.shop_id = shops.id
             LEFT JOIN saas_subscriptions subscriptions ON subscriptions.shop_id = shops.id
             LEFT JOIN saas_subscription_plans plans ON plans.id = subscriptions.plan_id
             GROUP BY shops.id, categories.id, subscriptions.id, plans.id
             ORDER BY shops.created_at DESC, shops.nom ASC"
        );

        return $statement->fetchAll();
    }

    public function findShop(int $id): ?array
    {
        $this->ensureSchema();
        $statement = Database::connection()->prepare(
            'SELECT shops.*, categories.nom AS category_name, categories.slug AS category_slug
             FROM shops
             LEFT JOIN shop_categories categories ON categories.id = shops.category_id
             WHERE shops.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $shop = $statement->fetch();

        return is_array($shop) ? $shop : null;
    }

    public function createShop(array $data): int
    {
        $payload = $this->shopPayload($data);
        $payload['slug'] = $this->uniqueShopSlug((string) $payload['nom']);
        $statement = Database::connection()->prepare(
            'INSERT INTO shops (category_id, nom, slug, adresse, telephone, email, devise_principale, taux_change_cdf, actif)
             VALUES (:category_id, :nom, :slug, :adresse, :telephone, :email, :devise_principale, :taux_change_cdf, :actif)'
        );
        $statement->execute($payload);

        return (int) Database::connection()->lastInsertId();
    }

    public function updateShop(int $id, array $data): bool
    {
        $payload = $this->shopPayload($data);
        $payload['id'] = $id;
        $statement = Database::connection()->prepare(
            'UPDATE shops
             SET nom = :nom,
                 adresse = :adresse,
                 telephone = :telephone,
                 email = :email,
                 category_id = :category_id,
                 devise_principale = :devise_principale,
                 taux_change_cdf = :taux_change_cdf,
                 actif = :actif
             WHERE id = :id'
        );
        $statement->execute($payload);

        return $statement->rowCount() > 0;
    }

    public function toggleShop(int $id): bool
    {
        $statement = Database::connection()->prepare('UPDATE shops SET actif = CASE WHEN actif = 1 THEN 0 ELSE 1 END WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    public function categories(bool $activeOnly = false): array
    {
        $this->ensureSchema();

        $sql = 'SELECT categories.*, COUNT(shops.id) AS shops_count,
                       SUM(CASE WHEN shops.id IS NOT NULL AND shops.actif = 1 THEN 1 ELSE 0 END) AS active_shops_count,
                       SUM(CASE WHEN shops.id IS NOT NULL AND shops.actif = 0 THEN 1 ELSE 0 END) AS inactive_shops_count
                FROM shop_categories categories
                LEFT JOIN shops ON shops.category_id = categories.id';

        if ($activeOnly) {
            $sql .= ' WHERE categories.actif = 1';
        }

        $sql .= ' GROUP BY categories.id, categories.nom, categories.slug, categories.description, categories.actif, categories.created_at, categories.updated_at
                  ORDER BY categories.nom ASC';

        return Database::connection()->query($sql)->fetchAll();
    }

    public function createCategory(array $data): int
    {
        $name = trim((string) ($data['nom'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException('Le nom de la categorie est obligatoire.');
        }

        $statement = Database::connection()->prepare(
            'INSERT INTO shop_categories (nom, slug, description, actif)
             VALUES (:nom, :slug, :description, :actif)'
        );
        $statement->execute([
            'nom' => $name,
            'slug' => $this->slug((string) ($data['slug'] ?? $name)),
            'description' => $this->nullableString($data['description'] ?? null),
            'actif' => isset($data['actif']) ? (int) (bool) $data['actif'] : 1,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function updateCategory(int $id, array $data): bool
    {
        $name = trim((string) ($data['nom'] ?? ''));

        if ($id < 1 || $name === '') {
            throw new InvalidArgumentException('Categorie invalide.');
        }

        $statement = Database::connection()->prepare(
            'UPDATE shop_categories
             SET nom = :nom,
                 slug = :slug,
                 description = :description,
                 actif = :actif
             WHERE id = :id'
        );
        $statement->execute([
            'nom' => $name,
            'slug' => $this->slug((string) ($data['slug'] ?? $name)),
            'description' => $this->nullableString($data['description'] ?? null),
            'actif' => isset($data['actif']) ? (int) (bool) $data['actif'] : 0,
            'id' => $id,
        ]);

        if ($statement->rowCount() > 0) {
            return true;
        }

        $exists = Database::connection()->prepare('SELECT 1 FROM shop_categories WHERE id = :id');
        $exists->execute(['id' => $id]);

        return (bool) $exists->fetchColumn();
    }

    public function toggleCategory(int $id): bool
    {
        if ($id < 1) {
            throw new InvalidArgumentException('Categorie invalide.');
        }

        $statement = Database::connection()->prepare('UPDATE shop_categories SET actif = IF(actif = 1, 0, 1) WHERE id = :id');
        $statement->execute(['id' => $id]);

        if ($statement->rowCount() > 0) {
            return true;
        }

        $exists = Database::connection()->prepare('SELECT 1 FROM shop_categories WHERE id = :id');
        $exists->execute(['id' => $id]);

        return (bool) $exists->fetchColumn();
    }

    public function deleteCategory(int $id): bool
    {
        if ($id < 1) {
            throw new InvalidArgumentException('Categorie invalide.');
        }

        $usage = Database::connection()->prepare('SELECT COUNT(*) FROM shops WHERE category_id = :id');
        $usage->execute(['id' => $id]);
        if ((int) $usage->fetchColumn() > 0) {
            throw new RuntimeException('Cette categorie contient des boutiques. Desactivez-la au lieu de la supprimer.');
        }

        $statement = Database::connection()->prepare('DELETE FROM shop_categories WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    public function allUsers(): array
    {
        $statement = Database::connection()->query(
            'SELECT users.*, roles.nom AS role_name, shops.nom AS shop_name
             FROM users
             LEFT JOIN roles ON roles.id = users.role_id
             LEFT JOIN shops ON shops.id = users.shop_id
             ORDER BY shops.nom ASC, users.nom ASC'
        );

        return $statement->fetchAll();
    }

    public function findUser(int $id): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT users.*, roles.nom AS role_name, shops.nom AS shop_name
             FROM users
             LEFT JOIN roles ON roles.id = users.role_id
             LEFT JOIN shops ON shops.id = users.shop_id
             WHERE users.id = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        return is_array($user) ? $user : null;
    }

    public function updateUserAccess(int $id, array $data): bool
    {
        $shopId = $this->nullablePositiveInt($data['shop_id'] ?? null);
        $roleId = $this->nullablePositiveInt($data['role_id'] ?? null);

        if ($shopId !== null && $roleId !== null) {
            $roleStatement = Database::connection()->prepare('SELECT nom FROM roles WHERE id = :id LIMIT 1');
            $roleStatement->execute(['id' => $roleId]);
            $roleName = strtolower(trim((string) $roleStatement->fetchColumn()));
            $asciiRoleName = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $roleName);
            $compactRoleName = preg_replace('/[^a-z0-9]+/', '', strtolower((string) $asciiRoleName)) ?? '';

            if (in_array($compactRoleName, ['admin', 'administrateur', 'administratrice', 'superadmin', 'superadministrateur', 'superadministratrice'], true)) {
                throw new InvalidArgumentException('Les rôles Admin et Super Admin sont réservés à l’administration SaaS et ne peuvent pas être liés à une boutique.');
            }
        }

        $statement = Database::connection()->prepare(
            'UPDATE users
             SET shop_id = :shop_id,
                 role_id = :role_id,
                 role_legacy = :role_legacy,
                 actif = :actif
             WHERE id = :id'
        );
        $statement->execute([
            'shop_id' => $shopId,
            'role_id' => $roleId,
            'role_legacy' => in_array(($data['role_legacy'] ?? 'agent'), ['admin', 'agent'], true) ? $data['role_legacy'] : 'agent',
            'actif' => isset($data['actif']) ? (int) (bool) $data['actif'] : 0,
            'id' => $id,
        ]);

        return $statement->rowCount() > 0;
    }

    public function roles(): array
    {
        return Database::connection()->query(
            'SELECT roles.*, COUNT(users.id) AS users_count
             FROM roles
             LEFT JOIN users ON users.role_id = roles.id
             GROUP BY roles.id, roles.nom, roles.permissions, roles.created_at
             ORDER BY roles.nom ASC'
        )->fetchAll();
    }

    public function createRole(array $data): int
    {
        $statement = Database::connection()->prepare('INSERT INTO roles (nom, permissions) VALUES (:nom, :permissions)');
        $statement->execute([
            'nom' => trim((string) ($data['nom'] ?? '')),
            'permissions' => json_encode($data['permissions'] ?? [], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function features(): array
    {
        $this->ensureSchema();

        return Database::connection()->query(
            'SELECT features.*,
                    COUNT(DISTINCT plan_features.plan_id) AS plans_count
             FROM saas_features features
             LEFT JOIN saas_plan_features plan_features ON plan_features.feature_id = features.id
             GROUP BY features.id, features.code, features.nom, features.description, features.categorie, features.actif, features.created_at, features.updated_at
             ORDER BY features.categorie ASC, features.nom ASC'
        )->fetchAll();
    }

    public function createFeature(array $data): int
    {
        $this->ensureSchema();
        $statement = Database::connection()->prepare(
            'INSERT INTO saas_features (code, nom, description, categorie, actif)
             VALUES (:code, :nom, :description, :categorie, :actif)'
        );
        $statement->execute($this->featurePayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public function updateFeature(int $id, array $data): bool
    {
        $payload = $this->featurePayload($data);
        $payload['id'] = $id;
        $statement = Database::connection()->prepare(
            'UPDATE saas_features
             SET code = :code, nom = :nom, description = :description, categorie = :categorie, actif = :actif
             WHERE id = :id'
        );
        $statement->execute($payload);

        return $statement->rowCount() > 0;
    }

    public function toggleFeature(int $id): bool
    {
        $this->ensureSchema();
        $statement = Database::connection()->prepare('UPDATE saas_features SET actif = CASE WHEN actif = 1 THEN 0 ELSE 1 END WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    public function featureAssignmentMaps(): array
    {
        $this->ensureSchema();

        return [
            'plans' => $this->assignmentMap('saas_plan_features', 'plan_id', 'feature_id'),
            'category_plans' => $this->assignmentMap('saas_category_plans', 'category_id', 'plan_id'),
        ];
    }

    public function featureShopAccessRows(): array
    {
        $this->ensureSchema();

        return Database::connection()->query(
            'SELECT
                shops.id,
                shops.nom AS shop_name,
                shops.actif AS shop_active,
                categories.nom AS category_name,
                categories.slug AS category_slug,
                plans.nom AS plan_name,
                subscriptions.statut AS subscription_status,
                subscriptions.date_fin,
                COUNT(DISTINCT features.id) AS features_count,
                GROUP_CONCAT(DISTINCT features.code ORDER BY features.code SEPARATOR ", ") AS feature_codes
             FROM shops
             LEFT JOIN shop_categories categories ON categories.id = shops.category_id
             LEFT JOIN saas_subscriptions subscriptions ON subscriptions.shop_id = shops.id
             LEFT JOIN saas_subscription_plans plans ON plans.id = subscriptions.plan_id
             LEFT JOIN saas_category_plans category_plans ON category_plans.category_id = shops.category_id
                AND category_plans.plan_id = subscriptions.plan_id
             LEFT JOIN saas_plan_features plan_features ON plan_features.plan_id = subscriptions.plan_id
             LEFT JOIN saas_features features ON features.id = plan_features.feature_id
                AND features.actif = 1
                AND category_plans.plan_id IS NOT NULL
             GROUP BY shops.id, shops.nom, shops.actif, categories.nom, categories.slug, plans.nom, subscriptions.statut, subscriptions.date_fin
             ORDER BY shops.nom ASC'
        )->fetchAll();
    }

    public function syncCategoryPlans(int $categoryId, array $planIds): void
    {
        $this->ensureSchema();
        $this->syncFeatureAssignments('saas_category_plans', 'category_id', 'plan_id', $categoryId, $planIds);
    }

    public function syncPlanFeatures(int $planId, array $featureIds): void
    {
        $this->ensureSchema();
        $this->syncFeatureAssignments('saas_plan_features', 'plan_id', 'feature_id', $planId, $featureIds);
    }

    public function plans(): array
    {
        $this->ensureSchema();

        return Database::connection()->query(
            'SELECT plans.*, COUNT(subscriptions.id) AS subscriptions_count
             FROM saas_subscription_plans plans
             LEFT JOIN saas_subscriptions subscriptions ON subscriptions.plan_id = plans.id
             WHERE plans.actif = 1
             GROUP BY plans.id, plans.nom, plans.code, plans.limite_boutiques, plans.prix_mensuel_usd, plans.limite_utilisateurs, plans.limite_produits, plans.description, plans.actif, plans.created_at, plans.updated_at
             ORDER BY plans.prix_mensuel_usd ASC, plans.nom ASC'
        )->fetchAll();
    }

    public function findPlan(int $id): ?array
    {
        $this->ensureSchema();
        $statement = Database::connection()->prepare(
            'SELECT plans.*, COUNT(subscriptions.id) AS subscriptions_count
             FROM saas_subscription_plans plans
             LEFT JOIN saas_subscriptions subscriptions ON subscriptions.plan_id = plans.id
             WHERE plans.id = :id
             GROUP BY plans.id, plans.nom, plans.code, plans.limite_boutiques, plans.prix_mensuel_usd, plans.limite_utilisateurs, plans.limite_produits, plans.description, plans.actif, plans.created_at, plans.updated_at
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $plan = $statement->fetch();

        return is_array($plan) ? $plan : null;
    }

    public function createPlan(array $data): int
    {
        $this->ensureSchema();
        $statement = Database::connection()->prepare(
            'INSERT INTO saas_subscription_plans (nom, code, limite_boutiques, prix_mensuel_usd, limite_utilisateurs, limite_produits, description, actif)
             VALUES (:nom, :code, :limite_boutiques, :prix_mensuel_usd, :limite_utilisateurs, :limite_produits, :description, :actif)'
        );
        $statement->execute($this->planPayload($data));

        return (int) Database::connection()->lastInsertId();
    }

    public function updatePlan(int $id, array $data): bool
    {
        $this->ensureSchema();
        $payload = $this->planPayload($data);
        $payload['id'] = $id;
        $statement = Database::connection()->prepare(
            'UPDATE saas_subscription_plans
             SET nom = :nom,
                 code = :code,
                 limite_boutiques = :limite_boutiques,
                 prix_mensuel_usd = :prix_mensuel_usd,
                 limite_utilisateurs = :limite_utilisateurs,
                 limite_produits = :limite_produits,
                 description = :description,
                 actif = :actif
             WHERE id = :id'
        );
        $statement->execute($payload);

        return $statement->rowCount() > 0;
    }

    public function deletePlan(int $id): bool
    {
        $this->ensureSchema();

        if ($id < 1) {
            throw new InvalidArgumentException('Plan invalide.');
        }

        $usage = Database::connection()->prepare('SELECT COUNT(*) FROM saas_subscriptions WHERE plan_id = :id');
        $usage->execute(['id' => $id]);

        if ((int) $usage->fetchColumn() > 0) {
            $statement = Database::connection()->prepare('UPDATE saas_subscription_plans SET actif = 0 WHERE id = :id');
            $statement->execute(['id' => $id]);

            return $statement->rowCount() > 0;
        }

        $statement = Database::connection()->prepare('DELETE FROM saas_subscription_plans WHERE id = :id');
        $statement->execute(['id' => $id]);

        return $statement->rowCount() > 0;
    }

    public function subscriptions(): array
    {
        $this->ensureSchema();

        return Database::connection()->query(
            "SELECT subscriptions.*, shops.nom AS shop_name, plans.nom AS plan_name, plans.prix_mensuel_usd
             FROM saas_subscriptions subscriptions
             INNER JOIN shops ON shops.id = subscriptions.shop_id
             LEFT JOIN saas_subscription_plans plans ON plans.id = subscriptions.plan_id
             ORDER BY subscriptions.updated_at DESC"
        )->fetchAll();
    }

    public function assignSubscription(int $shopId, array $data): bool
    {
        $this->ensureSchema();
        $planId = $this->nullablePositiveInt($data['plan_id'] ?? null);

        if ($planId === null) {
            throw new InvalidArgumentException('Le plan d abonnement est obligatoire pour une boutique.');
        }

        if (!$this->isPlanAllowedForShopCategory($shopId, $planId)) {
            throw new InvalidArgumentException('Ce plan n est pas disponible pour la categorie de cette boutique.');
        }

        $statement = Database::connection()->prepare(
            "INSERT INTO saas_subscriptions (shop_id, plan_id, statut, date_debut, date_fin, renouvellement_auto, notes)
             VALUES (:shop_id, :plan_id, :statut, :date_debut, :date_fin, :renouvellement_auto, :notes)
             ON DUPLICATE KEY UPDATE
                plan_id = VALUES(plan_id),
                statut = VALUES(statut),
                date_debut = VALUES(date_debut),
                date_fin = VALUES(date_fin),
                renouvellement_auto = VALUES(renouvellement_auto),
                notes = VALUES(notes)"
        );
        $statement->execute([
            'shop_id' => $shopId,
            'plan_id' => $planId,
            'statut' => $this->subscriptionStatus($data['statut'] ?? 'trial'),
            'date_debut' => $this->dateOrToday($data['date_debut'] ?? null),
            'date_fin' => $this->nullableDate($data['date_fin'] ?? null),
            'renouvellement_auto' => isset($data['renouvellement_auto']) ? 1 : 0,
            'notes' => $this->nullableString($data['notes'] ?? null),
        ]);

        return true;
    }

    public function featureIdsByShop(): array
    {
        $this->ensureSchema();
        $rows = Database::connection()->query(
            'SELECT shops.id AS shop_id, features.id AS feature_id
             FROM shops
             INNER JOIN saas_subscriptions subscriptions ON subscriptions.shop_id = shops.id
             INNER JOIN saas_category_plans category_plans ON category_plans.category_id = shops.category_id
                AND category_plans.plan_id = subscriptions.plan_id
             INNER JOIN saas_plan_features plan_features ON plan_features.plan_id = subscriptions.plan_id
             INNER JOIN saas_features features ON features.id = plan_features.feature_id
                AND features.actif = 1
             WHERE shops.category_id IS NOT NULL
               AND subscriptions.plan_id IS NOT NULL
               AND subscriptions.statut IN ("trial", "active")'
        )->fetchAll();
        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row['shop_id']][] = (int) $row['feature_id'];
        }

        return $map;
    }

    public function permissionsCatalog(): array
    {
        return [
            'all' => 'Acces complet',
            'pos_access' => 'Acceder a la caisse POS',
            'sales_view' => 'Voir les ventes',
            'customers_manage' => 'Gerer les clients',
            'products_manage' => 'Gerer les produits',
            'stock_adjust' => 'Ajuster le stock',
            'supplies_manage' => 'Gerer les approvisionnements',
            'expenses_add' => 'Ajouter des depenses',
            'reports_view' => 'Voir les rapports',
            'users_manage' => 'Gerer les utilisateurs boutique',
            'roles_manage' => 'Gerer les roles et permissions',
            'shop_settings' => 'Modifier les parametres boutique',
        ];
    }

    public function settings(): array
    {
        $this->ensureSchema();

        return Database::connection()->query(
            "SELECT *
             FROM saas_settings
             ORDER BY FIELD(group_name, 'platform', 'billing', 'access', 'general'), label ASC"
        )->fetchAll();
    }

    public function settingsMap(): array
    {
        $settings = [];

        foreach ($this->settings() as $setting) {
            $settings[(string) $setting['setting_key']] = $setting;
        }

        return $settings;
    }

    public function updateSettings(array $settings): void
    {
        $this->ensureSchema();
        $known = $this->settingsMap();
        $statement = Database::connection()->prepare(
            'UPDATE saas_settings SET setting_value = :setting_value WHERE setting_key = :setting_key'
        );

        foreach ($known as $key => $definition) {
            if (!array_key_exists($key, $settings)) {
                continue;
            }

            $statement->execute([
                'setting_key' => $key,
                'setting_value' => $this->normalizeSettingValue((string) ($definition['value_type'] ?? 'string'), $settings[$key]),
            ]);
        }
    }

    private function seedDefaults(): void
    {
        $pdo = Database::connection();
        $this->seedShopCategories();
        $this->seedSaasSettings();
        $featureCount = (int) $pdo->query('SELECT COUNT(*) FROM saas_features')->fetchColumn();

        if ($featureCount === 0) {
            $features = [
                ['pos', 'Caisse POS', 'Vente, facture et encaissement.', 'ventes'],
                ['stock', 'Stock et inventaire', 'Mouvements, ajustements et alertes.', 'stock'],
                ['reports', 'Rapports', 'Exports ventes, finances et stock.', 'pilotage'],
                ['customers', 'Clients et credits', 'Suivi clients, dettes et reglements.', 'ventes'],
                ['supplies', 'Approvisionnements', 'Arrivages et fournisseurs.', 'achats'],
                ['multi_currency', 'Multi-devise', 'Affichage USD/CDF selon la boutique.', 'finance'],
            ];
            $statement = $pdo->prepare('INSERT INTO saas_features (code, nom, description, categorie) VALUES (:code, :nom, :description, :categorie)');

            foreach ($features as $feature) {
                $statement->execute([
                    'code' => $feature[0],
                    'nom' => $feature[1],
                    'description' => $feature[2],
                    'categorie' => $feature[3],
                ]);
            }
        }

        $planCount = (int) $pdo->query('SELECT COUNT(*) FROM saas_subscription_plans')->fetchColumn();

        if ($planCount === 0) {
            $plans = [
                ['Starter', 'starter', 1, 3, 2, null, "1 boutique\n2 utilisateurs\nCaisse et tickets\nGestion des produits\nStock initial et seuils\nClients simples\nCreances clients\nRapports essentiels\nDevise boutique\nAssistance standard"],
                ['Business', 'business', 3, 7, 8, null, "3 boutiques\n8 utilisateurs\nToutes les fonctions Starter\nApprovisionnements fournisseurs\nAjustements de stock\nInventaire complet\nCharges et depenses\nHistorique des ventes\nRapports complets\nExports et impressions"],
                ['Pro', 'pro', 8, 12, 20, null, "8 boutiques\n20 utilisateurs\nToutes les fonctions Business\nGestion avancee des roles\nSuivi multi-boutiques\nAlertes stock renforcees\nAnalyse marges et charges\nSuivi avance des creances\nRapports financiers detailles\nAssistance prioritaire"],
                ['Reseau', 'reseau', null, 20, null, null, "Boutiques illimitees\nUtilisateurs illimites\nToutes les fonctions Pro\nPilotage reseau\nCentralisation des rapports\nGestion etendue des equipes\nSuivi global stock et ventes\nParametrage personnalise\nAccompagnement de deploiement\nAssistance prioritaire renforcee"],
            ];
            $statement = $pdo->prepare(
                'INSERT INTO saas_subscription_plans (nom, code, limite_boutiques, prix_mensuel_usd, limite_utilisateurs, limite_produits, description)
                 VALUES (:nom, :code, :limite_boutiques, :prix_mensuel_usd, :limite_utilisateurs, :limite_produits, :description)'
            );

            foreach ($plans as $plan) {
                $statement->execute([
                    'nom' => $plan[0],
                    'code' => $plan[1],
                    'limite_boutiques' => $plan[2],
                    'prix_mensuel_usd' => $plan[3],
                    'limite_utilisateurs' => $plan[4],
                    'limite_produits' => $plan[5],
                    'description' => $plan[6],
                ]);
            }
        }

        $this->seedCategoryPlans();
    }

    private function seedCategoryPlans(): void
    {
        $pdo = Database::connection();
        $linksCount = (int) $pdo->query('SELECT COUNT(*) FROM saas_category_plans')->fetchColumn();

        if ($linksCount > 0) {
            return;
        }

        $pdo->exec(
            'INSERT IGNORE INTO saas_category_plans (category_id, plan_id)
             SELECT categories.id, plans.id
             FROM shop_categories categories
             CROSS JOIN saas_subscription_plans plans
             WHERE categories.actif = 1
               AND plans.actif = 1'
        );

        $pdo->exec(
            "INSERT INTO saas_subscriptions (shop_id, plan_id, statut, date_debut, renouvellement_auto)
             SELECT shops.id, MIN(category_plans.plan_id), 'trial', CURRENT_DATE, 1
             FROM shops
             INNER JOIN saas_category_plans category_plans ON category_plans.category_id = shops.category_id
             LEFT JOIN saas_subscriptions subscriptions ON subscriptions.shop_id = shops.id
             WHERE subscriptions.id IS NULL
             GROUP BY shops.id"
        );
    }

    private function syncShopFeatures(int $shopId, array $featureIds): void
    {
        $pdo = Database::connection();
        $pdo->prepare('UPDATE saas_shop_features SET actif = 0 WHERE shop_id = :shop_id')->execute(['shop_id' => $shopId]);
        $statement = $pdo->prepare(
            "INSERT INTO saas_shop_features (shop_id, feature_id, actif)
             VALUES (:shop_id, :feature_id, 1)
             ON DUPLICATE KEY UPDATE actif = 1"
        );

        foreach (array_unique(array_filter($featureIds, static fn (int $id): bool => $id > 0)) as $featureId) {
            $statement->execute(['shop_id' => $shopId, 'feature_id' => $featureId]);
        }
    }

    private function assignmentMap(string $table, string $ownerColumn, string $assignedColumn): array
    {
        $rows = Database::connection()->query("SELECT {$ownerColumn} AS owner_id, {$assignedColumn} AS assigned_id FROM {$table}")->fetchAll();
        $map = [];

        foreach ($rows as $row) {
            $map[(int) $row['owner_id']][] = (int) $row['assigned_id'];
        }

        return $map;
    }

    private function syncFeatureAssignments(string $table, string $ownerColumn, string $assignedColumn, int $ownerId, array $assignedIds): void
    {
        if ($ownerId < 1) {
            throw new InvalidArgumentException('Selection invalide.');
        }

        $pdo = Database::connection();
        $pdo->prepare("DELETE FROM {$table} WHERE {$ownerColumn} = :owner_id")->execute(['owner_id' => $ownerId]);
        $statement = $pdo->prepare(
            "INSERT INTO {$table} ({$ownerColumn}, {$assignedColumn})
             VALUES (:owner_id, :assigned_id)"
        );

        foreach (array_unique(array_filter(array_map('intval', $assignedIds), static fn (int $id): bool => $id > 0)) as $assignedId) {
            $statement->execute([
                'owner_id' => $ownerId,
                'assigned_id' => $assignedId,
            ]);
        }
    }

    private function isPlanAllowedForShopCategory(int $shopId, int $planId): bool
    {
        $statement = Database::connection()->prepare(
            'SELECT COUNT(*)
             FROM shops
             INNER JOIN saas_category_plans category_plans ON category_plans.category_id = shops.category_id
             WHERE shops.id = :shop_id
               AND category_plans.plan_id = :plan_id'
        );
        $statement->execute([
            'shop_id' => $shopId,
            'plan_id' => $planId,
        ]);

        return (int) $statement->fetchColumn() > 0;
    }

    private function shopPayload(array $data): array
    {
        return [
            'category_id' => $this->nullablePositiveInt($data['category_id'] ?? null) ?? $this->defaultCategoryId(),
            'nom' => trim((string) ($data['nom'] ?? '')),
            'adresse' => $this->nullableString($data['adresse'] ?? null),
            'telephone' => $this->nullableString($data['telephone'] ?? null),
            'email' => $this->nullableString($data['email'] ?? null),
            'devise_principale' => in_array(($data['devise_principale'] ?? 'USD'), ['USD', 'CDF'], true) ? $data['devise_principale'] : 'USD',
            'taux_change_cdf' => max(1, (float) ($data['taux_change_cdf'] ?? 2800)),
            'actif' => isset($data['actif']) ? (int) (bool) $data['actif'] : 0,
        ];
    }

    private function seedShopCategories(): void
    {
        $categories = [
            ['Boutiques', 'boutiques'],
            ['Pharmacies', 'pharmacies'],
            ['Quincailleries', 'quincailleries'],
            ['Supermarches', 'supermarches'],
            ['Depots', 'depots'],
            ['Papeteries', 'papeteries'],
            ['Librairies', 'librairies'],
            ['Boulangeries', 'boulangeries'],
            ['Restaurants', 'restaurants'],
            ['Bars', 'bars'],
            ['Hotels', 'hotels'],
            ['Magasins de vetements', 'magasins-de-vetements'],
            ['Magasins d\'electronique', 'magasins-d-electronique'],
            ['Grossistes', 'grossistes'],
            ['Distributeurs', 'distributeurs'],
            ['Entreprises commerciales', 'entreprises-commerciales'],
        ];
        $statement = Database::connection()->prepare(
            'INSERT INTO shop_categories (nom, slug, actif)
             VALUES (:nom, :slug, 1)
             ON DUPLICATE KEY UPDATE id = id'
        );

        foreach ($categories as [$name, $slug]) {
            $statement->execute([
                'nom' => $name,
                'slug' => $slug,
            ]);
        }

        $descriptions = [
            'boutiques' => 'Commerce de detail general avec catalogue produits, caisse, stock, clients et rapports.',
            'pharmacies' => 'Gestion des medicaments, lots, dates d expiration, stock sensible et ventes controlees.',
            'quincailleries' => 'Articles de construction, outillage, quincaillerie, stock par references et approvisionnements.',
            'supermarches' => 'Vente rapide multi-rayons avec caisse, inventaire, familles produits et volumes importants.',
            'depots' => 'Gestion de stock entrepose, entrees, sorties, inventaires et suivi des mouvements.',
            'papeteries' => 'Articles scolaires, fournitures de bureau, petits accessoires et vente au comptoir.',
            'librairies' => 'Livres, manuels, auteurs, editions et stock de references culturelles ou scolaires.',
            'boulangeries' => 'Produits frais, production journaliere, ventes rapides, pertes et suivi des invendus.',
            'restaurants' => 'Menus, plats, boissons, caisse restaurant, depenses et pilotage journalier.',
            'bars' => 'Boissons, consommations, caisse rapide, stock de bouteilles et suivi des recettes.',
            'hotels' => 'Services hoteliers, chambres, prestations, facturation client et suivi d activite.',
            'magasins-de-vetements' => 'Articles textiles, tailles, collections, stock boutique et ventes au detail.',
            'magasins-d-electronique' => 'Appareils, accessoires, references techniques, stock et garanties commerciales.',
            'grossistes' => 'Vente en gros, lots, prix de volume, grands stocks et clients professionnels.',
            'distributeurs' => 'Distribution multi-clients, approvisionnement, suivi des sorties et rapports de reseau.',
            'entreprises-commerciales' => 'Activite commerciale polyvalente avec ventes, achats, finances et reporting.',
            'vendeur-forfait-mobile-unites' => 'Vente d unites, forfaits mobiles, recharges et suivi des operations de telecommunication.',
        ];
        $descriptionStatement = Database::connection()->prepare(
            'INSERT INTO shop_categories (nom, slug, description, actif)
             VALUES (:nom, :slug, :description, 1)
             ON DUPLICATE KEY UPDATE id = id'
        );

        foreach ($descriptions as $slug => $description) {
            $name = [
                'supermarches' => 'Supermarches',
                'depots' => 'Depots',
                'hotels' => 'Hotels',
                'magasins-de-vetements' => 'Magasins de vetements',
                'magasins-d-electronique' => 'Magasins d electronique',
                'vendeur-forfait-mobile-unites' => 'Vendeur forfait mobile (Unites)',
            ][$slug] ?? ucwords(str_replace('-', ' ', $slug));

            $descriptionStatement->execute([
                'nom' => $name,
                'slug' => $slug,
                'description' => $description,
            ]);
        }

        $defaultCategoryId = $this->defaultCategoryId();

        if ($defaultCategoryId !== null) {
            Database::connection()
                ->prepare('UPDATE shops SET category_id = :category_id WHERE category_id IS NULL')
                ->execute(['category_id' => $defaultCategoryId]);
        }
    }

    private function seedSaasSettings(): void
    {
        $settings = [
            ['platform_name', 'MadukaOne SaaS', 'string', 'Nom de la plateforme', 'Nom affiche dans l espace SaaS et les communications.', 'platform'],
            ['support_email', 'support@madukaone.local', 'string', 'Email support', 'Adresse de contact pour les boutiques clientes.', 'platform'],
            ['support_phone', '', 'string', 'Telephone support', 'Numero de support commercial ou technique.', 'platform'],
            ['default_currency', 'USD', 'string', 'Devise par defaut', 'Devise appliquee aux nouvelles offres SaaS.', 'billing'],
            ['default_trial_days', '14', 'integer', 'Jours d essai par defaut', 'Duree initiale accordee aux nouvelles boutiques.', 'billing'],
            ['billing_grace_days', '7', 'integer', 'Delai de grace paiement', 'Nombre de jours avant suspension apres echeance.', 'billing'],
            ['allow_new_shops', '1', 'boolean', 'Autoriser les nouvelles boutiques', 'Controle la creation de boutiques depuis l espace SaaS.', 'access'],
            ['maintenance_mode', '0', 'boolean', 'Mode maintenance SaaS', 'Indique une maintenance globale de la plateforme.', 'access'],
        ];
        $statement = Database::connection()->prepare(
            "INSERT INTO saas_settings (setting_key, setting_value, value_type, label, description, group_name)
             VALUES (:setting_key, :setting_value, :value_type, :label, :description, :group_name)
             ON DUPLICATE KEY UPDATE
                value_type = VALUES(value_type),
                label = VALUES(label),
                description = VALUES(description),
                group_name = VALUES(group_name)"
        );

        foreach ($settings as [$key, $value, $type, $label, $description, $group]) {
            $statement->execute([
                'setting_key' => $key,
                'setting_value' => $value,
                'value_type' => $type,
                'label' => $label,
                'description' => $description,
                'group_name' => $group,
            ]);
        }
    }

    private function ensureShopCategoryColumn(): void
    {
        $pdo = Database::connection();
        $column = $pdo->query(
            "SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'shops'
               AND COLUMN_NAME = 'category_id'"
        )->fetchColumn();

        if ((int) $column === 0) {
            $pdo->exec('ALTER TABLE shops ADD COLUMN category_id BIGINT UNSIGNED NULL AFTER id');
        }

        $index = $pdo->query(
            "SELECT COUNT(*)
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'shops'
               AND INDEX_NAME = 'idx_shops_category_id'"
        )->fetchColumn();

        if ((int) $index === 0) {
            $pdo->exec('ALTER TABLE shops ADD KEY idx_shops_category_id (category_id)');
        }
    }

    private function ensureShopCategoryDescriptionColumn(): void
    {
        $pdo = Database::connection();
        $column = $pdo->query(
            "SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'shop_categories'
               AND COLUMN_NAME = 'description'"
        )->fetchColumn();

        if ((int) $column === 0) {
            $pdo->exec('ALTER TABLE shop_categories ADD COLUMN description TEXT NULL AFTER slug');
        }
    }

    private function ensurePlanShopLimitColumn(): void
    {
        $pdo = Database::connection();
        $column = $pdo->query(
            "SELECT COUNT(*)
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'saas_subscription_plans'
               AND COLUMN_NAME = 'limite_boutiques'"
        )->fetchColumn();

        if ((int) $column === 0) {
            $pdo->exec('ALTER TABLE saas_subscription_plans ADD COLUMN limite_boutiques INT NULL AFTER code');
        }
    }

    private function defaultCategoryId(): ?int
    {
        try {
            $statement = Database::connection()->prepare('SELECT id FROM shop_categories WHERE slug = :slug LIMIT 1');
            $statement->execute(['slug' => 'boutiques']);
            $id = (int) $statement->fetchColumn();

            return $id > 0 ? $id : null;
        } catch (Throwable) {
            return null;
        }
    }

    private function slug(string $value): string
    {
        $value = trim($value);
        $transliterated = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        $slug = strtolower((string) ($transliterated !== false ? $transliterated : $value));
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'categorie-' . bin2hex(random_bytes(3));
    }

    private function uniqueShopSlug(string $name): string
    {
        $base = substr($this->slug($name), 0, 150);

        if (in_array($base, [
            'home', 'accueil', 'privacy', 'confidentialite', 'terms', 'conditions', 'pricing',
            'register-store', 'dashboard', 'login', 'logout', 'activate', 'auth', 'saas-admin',
            'shops', 'boutiques', 'products', 'pos', 'caisse', 'sales', 'ventes', 'customers',
            'clients', 'roles', 'users', 'admin', 'supplies', 'suppliers', 'fournisseurs', 'stock',
            'expenses', 'finances', 'reports', 'rapports', 'pharmacy', 'pharmacie', 'fashion',
            'vetements', 'profil', 'profile', 'backup',
        ], true)) {
            $base = 'boutique-' . $base;
        }

        $candidate = $base;
        $suffix = 2;
        $statement = Database::connection()->prepare('SELECT COUNT(*) FROM shops WHERE slug = :slug');

        while (true) {
            $statement->execute(['slug' => $candidate]);

            if ((int) $statement->fetchColumn() === 0) {
                return $candidate;
            }

            $candidate = substr($base, 0, 150) . '-' . $suffix;
            $suffix++;
        }
    }

    private function featurePayload(array $data): array
    {
        $code = strtolower(preg_replace('/[^a-z0-9_]+/', '_', trim((string) ($data['code'] ?? ''))) ?? '');

        return [
            'code' => trim($code, '_'),
            'nom' => trim((string) ($data['nom'] ?? '')),
            'description' => $this->nullableString($data['description'] ?? null),
            'categorie' => trim((string) ($data['categorie'] ?? 'general')) ?: 'general',
            'actif' => isset($data['actif']) ? (int) (bool) $data['actif'] : 0,
        ];
    }

    private function planPayload(array $data): array
    {
        $code = strtolower(preg_replace('/[^a-z0-9_]+/', '_', trim((string) ($data['code'] ?? ''))) ?? '');
        $name = trim((string) ($data['nom'] ?? ''));
        $code = trim($code, '_');

        if ($name === '' || $code === '') {
            throw new InvalidArgumentException('Le nom et le code du plan sont obligatoires.');
        }

        return [
            'nom' => $name,
            'code' => $code,
            'limite_boutiques' => $this->nullablePositiveInt($data['limite_boutiques'] ?? null),
            'prix_mensuel_usd' => max(0, (float) ($data['prix_mensuel_usd'] ?? 0)),
            'limite_utilisateurs' => $this->nullablePositiveInt($data['limite_utilisateurs'] ?? null),
            'limite_produits' => $this->nullablePositiveInt($data['limite_produits'] ?? null),
            'description' => $this->nullableString($data['description'] ?? null),
            'actif' => isset($data['actif']) ? (int) (bool) $data['actif'] : 0,
        ];
    }

    private function subscriptionStatus(mixed $value): string
    {
        $value = (string) $value;

        return in_array($value, ['trial', 'active', 'past_due', 'suspended', 'cancelled'], true) ? $value : 'trial';
    }

    private function dateOrToday(mixed $value): string
    {
        $date = $this->nullableDate($value);

        return $date ?? date('Y-m-d');
    }

    private function nullableDate(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) === 1 ? $value : null;
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        $value = (int) ($value ?? 0);

        return $value > 0 ? $value : null;
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }

    private function normalizeSettingValue(string $type, mixed $value): string
    {
        return match ($type) {
            'boolean' => in_array((string) $value, ['1', 'true', 'on', 'yes'], true) ? '1' : '0',
            'integer' => (string) max(0, (int) $value),
            'decimal' => (string) max(0, (float) $value),
            'json' => is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
            default => trim((string) $value),
        };
    }
}
