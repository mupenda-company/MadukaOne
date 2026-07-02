<?php

return [
    ['GET', '/', 'DashboardController@index'],
    ['GET', '/dashboard', 'DashboardController@index'],
    ['GET', '/login', 'AuthController@login'],
    ['POST', '/login', 'AuthController@authenticate'],
    ['GET', '/auth/google', 'AuthController@redirectToGoogle'],
    ['GET', '/auth/google/callback', 'AuthController@googleCallback'],
    ['GET', '/auth/apple', 'AuthController@redirectToApple'],
    ['POST', '/auth/apple/callback', 'AuthController@appleCallback'],
    ['GET', '/auth/apple/callback', 'AuthController@appleCallback'],
    ['POST', '/logout', 'AuthController@logout'],
    ['GET', '/profil', 'UserController@profile'],
    ['GET', '/profile', 'UserController@profile'],
    ['GET', '/pos', 'PosController@index'],
    ['POST', '/pos/sale', 'PosController@store'],
    ['GET', '/caisse', 'PosController@index'],
    ['POST', '/caisse/vente', 'PosController@store'],
    ['GET', '/rapports/ventes', 'ReportController@sales'],
    ['GET', '/rapports/stock', 'ReportController@stockMovements'],
];
