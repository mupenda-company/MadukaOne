<?php

declare(strict_types=1);

require_once __DIR__ . '/../Core/Database.php';

final class LegalContent
{
    private const TABLES = [
        'privacy' => 'saas_privacy_sections',
        'terms' => 'saas_terms_sections',
    ];

    public function sections(string $type, bool $publicOnly = false): array
    {
        $table = $this->table($type);
        $where = $publicOnly ? ' WHERE actif = 1' : '';

        try {
            return Database::connection()->query(
                "SELECT id, titre, contenu, ordre, actif, created_at, updated_at FROM {$table}{$where} ORDER BY ordre ASC, id ASC"
            )->fetchAll();
        } catch (Throwable) {
            return [];
        }
    }

    public function create(string $type, array $data): void
    {
        $table = $this->table($type);
        [$title, $content, $order] = $this->validated($data);
        $statement = Database::connection()->prepare("INSERT INTO {$table} (titre, contenu, ordre, actif) VALUES (?, ?, ?, ?)");
        $statement->execute([$title, $content, $order, isset($data['actif']) ? 1 : 0]);
    }

    public function update(string $type, int $id, array $data): void
    {
        if ($id < 1) {
            throw new InvalidArgumentException('Section invalide.');
        }
        $table = $this->table($type);
        [$title, $content, $order] = $this->validated($data);
        $statement = Database::connection()->prepare("UPDATE {$table} SET titre = ?, contenu = ?, ordre = ?, actif = ? WHERE id = ?");
        $statement->execute([$title, $content, $order, isset($data['actif']) ? 1 : 0, $id]);
    }

    public function toggle(string $type, int $id): void
    {
        $table = $this->table($type);
        $statement = Database::connection()->prepare("UPDATE {$table} SET actif = IF(actif = 1, 0, 1) WHERE id = ?");
        $statement->execute([$id]);
    }

    public function delete(string $type, int $id): void
    {
        $table = $this->table($type);
        $statement = Database::connection()->prepare("DELETE FROM {$table} WHERE id = ?");
        $statement->execute([$id]);
    }

    private function table(string $type): string
    {
        if (!isset(self::TABLES[$type])) {
            throw new InvalidArgumentException('Document légal invalide.');
        }
        return self::TABLES[$type];
    }

    private function validated(array $data): array
    {
        $title = trim((string) ($data['titre'] ?? ''));
        $content = trim((string) ($data['contenu'] ?? ''));
        if ($title === '' || $content === '') {
            throw new InvalidArgumentException('Le titre et le contenu sont obligatoires.');
        }
        return [$title, $content, max(0, (int) ($data['ordre'] ?? 0))];
    }
}
