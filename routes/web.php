<?php

return [
    ['GET', '/', 'DashboardController@index'],
    ['GET', '/dashboard', 'DashboardController@index'],

    ['GET', '/login', 'AuthController@login'],
    ['POST', '/login', 'AuthController@authenticate'],
    ['GET', '/logout', 'AuthController@logout'],
    ['POST', '/logout', 'AuthController@logout'],
    ['GET', '/auth/google', 'AuthController@redirectToGoogle'],
    ['GET', '/auth/google/callback', 'AuthController@googleCallback'],
    ['GET', '/auth/apple', 'AuthController@redirectToApple'],
    ['POST', '/auth/apple/callback', 'AuthController@appleCallback'],
    ['GET', '/auth/apple/callback', 'AuthController@appleCallback'],

    ['GET', '/profil', 'UserController@profile'],
    ['GET', '/profile', 'UserController@profile'],
    ['GET', '/users', 'UserController@index'],
    ['GET', '/roles', 'RoleController@index'],
    ['GET', '/roles/create', 'RoleController@create'],
    ['POST', '/roles', 'RoleController@store'],
    ['GET', '/roles-permissions', 'RoleController@index'],

    ['GET', '/pos', 'PosController@index'],
    ['POST', '/pos/sale', 'PosController@store'],
    ['GET', '/caisse', 'PosController@index'],
    ['POST', '/caisse/vente', 'PosController@store'],

    ['GET', '/products', 'ProductController@index'],
    ['GET', '/products/create', 'ProductController@create'],
    ['POST', '/products', 'ProductController@store'],
    ['GET', '/products/{id}', 'ProductController@show'],
    ['GET', '/products/{id}/edit', 'ProductController@edit'],
    ['POST', '/products/{id}/update', 'ProductController@update'],
    ['POST', '/products/{id}/delete', 'ProductController@destroy'],

    ['GET', '/supplies', 'SupplyController@index'],
    ['GET', '/supplies/create', 'SupplyController@create'],
    ['POST', '/supplies', 'SupplyController@store'],
    ['POST', '/supplies/create', 'SupplyController@store'],
    ['GET', '/supplies/{id}', 'SupplyController@show'],

    ['GET', '/stock/movements', 'StockController@movements'],
    ['GET', '/stock/adjustments', 'StockController@adjustments'],
    ['POST', '/stock/adjustments', 'StockController@storeAdjustment'],
    ['GET', '/stock', 'StockController@movements'],

    ['GET', '/expenses', 'ExpenseController@index'],
    ['GET', '/expenses/create', 'ExpenseController@create'],
    ['POST', '/expenses', 'ExpenseController@store'],
    ['GET', '/expenses/{id}', 'ExpenseController@show'],
    ['GET', '/finances', 'ExpenseController@index'],
    ['POST', '/finances', 'ExpenseController@store'],

    ['GET', '/reports/financials', 'ReportController@financials'],
    ['GET', '/reports/sales', 'ReportController@sales'],
    ['GET', '/reports/stock', 'ReportController@stockMovements'],
    ['GET', '/rapports/finances', 'ReportController@financials'],
    ['GET', '/rapports/ventes', 'ReportController@sales'],
    ['GET', '/rapports/stock', 'ReportController@stockMovements'],
    ['GET', '/rapports/financiers', 'ReportController@financials'],
    ['GET', '/backup/manual', 'ReportController@backup'],
];
