# Architecture technique - Shop Logistique POS (Version ERP Complete)

## Objectif

Application PHP pure multi-boutiques de gestion de stock, d'approvisionnements, de point de vente (POS) et de comptabilite. Elle est concue pour permettre au proprietaire de piloter son activite a distance en temps reel, de suivre sa sante financiere globale, et de limiter radicalement les possibilites de fraude, de vol ou de manipulation par les agents et vendeurs.

## Arborescence MVC

```text
Shop_logistique/
  app/
    Controllers/
      AuthController.php
      CustomerController.php
      DashboardController.php
      ExpenseController.php
      FinanceController.php
      PosController.php
      ProductController.php
      ReportController.php
      ShopController.php
      StockController.php
      SupplierController.php
      SupplyController.php
      UserController.php
    Core/
      App.php
      Auth.php
      Backup.php
      Controller.php
      Database.php
      Middleware.php
      Model.php
      Router.php
      Session.php
      Validator.php
    Models/
      Customer.php
      Expense.php
      Product.php
      Role.php
      Sale.php
      SaleDetail.php
      Shop.php
      StockMovement.php
      Supplier.php
      Supply.php
      SupplyDetail.php
      User.php
    Views/
      auth/
        login.php
      customers/
        index.php
        show.php
      dashboard/
        index.php
      expenses/
        index.php
      finances/
        index.php
      layouts/
        app.php
        auth.php
        partials/
          flash.php
          navbar.php
          sidebar.php
      pos/
        index.php
        invoice.php
        receipt.php
      products/
        create.php
        edit.php
        index.php
        show.php
      reports/
        financials.php
        sales.php
        stock-movements.php
      shops/
        index.php
      stock/
        adjustments.php
        movements.php
      suppliers/
        index.php
      supplies/
        create.php
        index.php
        show.php
      users/
        create.php
        edit.php
        index.php
        roles.php
  config/
    app.php
    database.php
    storage.php
  database/
    schema.sql
  public/
    .htaccess
    index.php
    assets/
      css/
        app.css
      js/
        pos.js
        print.js
  routes/
    web.php
  storage/
    backups/
      .gitkeep
    logs/
      .gitkeep
  vendor/
    .gitkeep
  .env.example
  ARCHITECTURE.md
```

## Modules metier

- Authentification et roles: administrateur, agent, permissions granulaires.
- POS/Caisse: vente rapide, ticket thermique, facture officielle.
- Stock: mouvements obligatoires, ajustements traces, alertes de stock.
- Produits: prix d'achat, prix de vente, reference et code-barres.
- Rapports: ventes, mouvements de stock, benefices et chiffres financiers.
- Multi-boutiques: suivi par boutique et bascule de contexte.
- Fournisseurs et approvisionnements: arrivages, details d'achat, entrees de stock.
- Clients et dettes: historique client, creances et suivi des paiements.
- Finances: depenses, tresorerie, clotures de caisse, benefice net.
- Sauvegardes: preparation d'un module de backup local/cloud.

## Regles anti-fraude

- Un agent ne modifie jamais les prix.
- Un agent ne modifie jamais le stock manuellement.
- Une vente validee reste dans l'historique.
- Un mouvement de stock est obligatoire pour toute variation de quantite.
- Les rapports financiers sont reserves a l'administrateur.
- Les operations sensibles doivent utiliser PDO, requetes preparees et transactions SQL.

