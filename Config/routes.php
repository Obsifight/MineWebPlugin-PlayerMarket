<?php
Router::connect('/market', array('controller' => 'display', 'action' => 'index', 'plugin' => 'PlayerMarket'));
Router::connect('/market/purchase/:id', array('controller' => 'purchase', 'action' => 'buyWithPoints', 'plugin' => 'PlayerMarket'));
