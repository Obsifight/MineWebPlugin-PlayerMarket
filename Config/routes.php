<?php
Router::connect('/market', array('controller' => 'display', 'action' => 'index', 'plugin' => 'PlayerMarket'));
Router::connect('/market/user/money', array('controller' => 'display', 'action' => 'getUserMoney', 'plugin' => 'PlayerMarket'));
Router::connect('/market/uuids', array('controller' => 'display', 'action' => 'getUUIDs', 'plugin' => 'PlayerMarket'));
Router::connect('/market/purchase/points/:id', array('controller' => 'purchase', 'action' => 'buyWithPoints', 'plugin' => 'PlayerMarket'));
Router::connect('/market/purchase/money/:id', array('controller' => 'purchase', 'action' => 'buyWithMoney', 'plugin' => 'PlayerMarket'));
Router::connect('/market/purchase/recovery/:id', array('controller' => 'purchase', 'action' => 'recovery', 'plugin' => 'PlayerMarket'));

Router::connect('/admin/market/items', array('controller' => 'display', 'action' => 'items', 'plugin' => 'PlayerMarket', 'admin' => true));
Router::connect('/admin/market/items/refresh', array('controller' => 'display', 'action' => 'items_refresh', 'plugin' => 'PlayerMarket', 'admin' => true));
Router::connect('/admin/market/history', array('controller' => 'display', 'action' => 'histories', 'plugin' => 'PlayerMarket', 'admin' => true));
Router::connect('/admin/market/history/data', array('controller' => 'display', 'action' => 'get_histories', 'plugin' => 'PlayerMarket', 'admin' => true));
Router::connect('/admin/market/infos', array('controller' => 'display', 'action' => 'infos', 'plugin' => 'PlayerMarket', 'admin' => true));
Router::connect('/admin/market/infos/enable', array('controller' => 'display', 'action' => 'enable', 'plugin' => 'PlayerMarket', 'admin' => true));
Router::connect('/admin/market/infos/disable', array('controller' => 'display', 'action' => 'disable', 'plugin' => 'PlayerMarket', 'admin' => true));
