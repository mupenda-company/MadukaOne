<?php

return [
    ['GET', '/', 'DashboardController@index'],
    ['GET', '/login', 'AuthController@login'],
    ['POST', '/login', 'AuthController@authenticate'],
    ['POST', '/logout', 'AuthController@logout'],
    ['GET', '/caisse', 'PosController@index'],
    ['POST', '/caisse/vente', 'PosController@store'],
    ['GET', '/rapports/ventes', 'ReportController@sales'],
    ['GET', '/rapports/stock', 'ReportController@stockMovements'],
];

