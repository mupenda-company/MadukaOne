<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/Core/Database.php';
require_once dirname(__DIR__) . '/Core/Model.php';

final class Expense extends Model
{
    public function allByShop(int $shopId): array
    {
        $statement = Database::connection()->prepare(
            'SELECT expenses.*, users.nom AS user_name
             FROM expenses
             INNER JOIN users ON users.id = expenses.user_id
             WHERE expenses.shop_id = :shop_id
             ORDER BY expenses.date_depense DESC, expenses.id DESC'
        );
        $statement->execute(['shop_id' => $shopId]);

        return $statement->fetchAll();
    }

    public function findByShop(int $id, int $shopId): ?array
    {
        $statement = Database::connection()->prepare(
            'SELECT expenses.*, users.nom AS user_name
             FROM expenses
             INNER JOIN users ON users.id = expenses.user_id
             WHERE expenses.id = :id AND expenses.shop_id = :shop_id
             LIMIT 1'
        );
        $statement->execute([
            'id' => $id,
            'shop_id' => $shopId,
        ]);

        $expense = $statement->fetch();

        return is_array($expense) ? $expense : null;
    }

    public function create(array $data, int $shopId, int $userId): int
    {
        $statement = Database::connection()->prepare(
            'INSERT INTO expenses (
                shop_id, user_id, titre, description, montant, categorie, date_depense
             ) VALUES (
                :shop_id, :user_id, :titre, :description, :montant, :categorie, :date_depense
             )'
        );

        $statement->execute([
            'shop_id' => $shopId,
            'user_id' => $userId,
            'titre' => trim((string) $data['titre']),
            'description' => $this->nullableString($data['description'] ?? null),
            'montant' => (float) $data['montant'],
            'categorie' => $this->validCategory((string) ($data['categorie'] ?? 'autre')),
            'date_depense' => $data['date_depense'] ?: date('Y-m-d H:i:s'),
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    private function validCategory(string $category): string
    {
        $category = strtolower(trim($category));
        $allowed = ['transport', 'facture', 'loyer', 'salaire', 'perte_avarie', 'autre'];

        return in_array($category, $allowed, true) ? $category : 'autre';
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) ($value ?? ''));

        return $value === '' ? null : $value;
    }
}
