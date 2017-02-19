<?php
class DisplayController extends PlayerMarketAppController {

  public function beforeFilter() {
    parent::beforeFilter();
    $this->serverId = Configure::read('ObsiPlugin.server.pvp.id');
    //$this->serverId = 6;
    $this->Security->unlockedActions = array('admin_items');
  }

  public function index() {
    $this->set('title_for_layout', $this->Lang->get('PLAYERMARKET__HOMEPAGE_TITLE'));

    // get state
    $this->loadModel('PlayerMarket.MinecraftItem');
    $state = ($this->MinecraftItem->find('count', array('conditions' => array('minecraft_id' => '-1', 'name' => 'DISABLED'))) === 0);
    $this->set('state', $state);
    if (!$state)
      return;

    // Check if he can see shop (already connected to launcher + logged)
    if (!$this->isConnected)
      return $this->set('canViewMarket', false);
    // Check launcher logs
    App::uses('ConnectionManager', 'Model');
    $con = new ConnectionManager;
    try {
      ConnectionManager::create('Util', Configure::read('Obsi.db.Util'));
    } catch (Exception $e) {
      $this->log('Error: '.$e->getMessage());
      $error = true;
    }
    if (!isset($error)) {
      $dbUtil = $con->getDataSource('Util');
      $launcherConnectionLogs = $dbUtil->query("SELECT * FROM loginlogs WHERE username = '{$this->User->getKey('pseudo')}' ORDER BY id DESC");
      if (empty($launcherConnectionLogs))
        return $this->set('canViewMarket', false);
    }
    $this->set('canViewMarket', true); // he can

    $this->loadModel('PlayerMarket.Sale');
    $this->Sale->apiComponent = $this->Components->load('Obsi.Api');
    $this->set('sales', array_map(function ($sale) {
      // display items
      foreach ($sale['items'] as $k => $item) {
        $name = $item['name'];
        if (strpos($name, '<span>') === 0)
          $name = '<span class="text-info">'.$name.'</span>';
        $sale['items'][$k]['name_parsed'] =  '<span class="label label-info">' . $item['amount'] . '</span>' . ' ' . $name;
        if (!empty($item['enchantments'])) {
          $sale['items'][$k]['name_parsed'] .= '&nbsp;(<em>'.implode(', ', array_map(function ($enchant) {
            return implode(' ', $enchant);
          }, $item['enchantments'])).'</em>)';
        }
      }
      return $sale;
    }, $this->Sale->getAll()));
    if ($this->isConnected)
      $this->set('mySales', $this->Sale->getFrom($this->User->getKey('pseudo')));
    else
      $this->set('mySales', array());
  }

  public function getUserMoney() {
    $this->autoRender = false;
    $this->response->type('json');
    if (!$this->isConnected)
      return $this->response->body(json_encode(array('status' => false, 'money' => 0)));

    $callMoney = $this->Server->call(array('getPlayerMoney' => $this->User->getKey('pseudo')), true, $this->serverId);
    if (!$callMoney || $callMoney['getPlayerMoney'] == 'PLAYER_NOT_CONNECTED')
      return $this->response->body(json_encode(array('status' => false, 'money' => 0)));
    $this->response->body(json_encode(array('status' => true, 'money' => floatval($callMoney['getPlayerMoney']))));
  }

  public function getUUIDs() {
    $this->autoRender = false;
    $this->response->type('json');
    if (!$this->request->is('post'))
      throw new BadRequestException();

    $this->apiComponent = $this->Components->load('Obsi.Api');
    $result = $this->apiComponent->get('/user/infos/username', 'POST', array('uuids' => $this->request->data['uuids']));
    if (!$result->status || !$result->success)
      throw new InternalErrorException();
    return $this->response->body(json_encode(array('status' => true, 'body' => $result->body)));
  }

  public function admin_items() {
    if (!$this->isConnected || !$this->Permissions->can('PLAYERMARKET__EDIT_ITEMS'))
      throw new ForbiddenException();
    $this->layout = 'admin';
    $this->set('title_for_layout', 'Éditer les items');

    $this->loadModel('PlayerMarket.MinecraftItem');

    if ($this->request->is('post')) { // save
      $this->MinecraftItem->saveAll($this->request->data['items']);
    }

    $this->set('items', $this->MinecraftItem->find('all'));
  }

  public function admin_histories() {
    if (!$this->isConnected || !$this->Permissions->can('PLAYERMARKET__VIEW_HISTORY'))
      throw new ForbiddenException();
    $this->layout = 'admin';
    $this->set('title_for_layout', 'Historique des ventes');
  }

  public function admin_get_histories() {
    if (!$this->isConnected || !$this->Permissions->can('PLAYERMARKET__VIEW_HISTORY'))
      throw new ForbiddenException();
    $this->autoRender = false;
    $this->response->type('json');

    $this->DataTable = $this->Components->load('DataTable');
    $this->modelClass = 'PurchaseHistory';
    $this->DataTable->initialize($this);
    $this->paginate = array(
      'fields' => array($this->modelClass.'.id',$this->modelClass.'.user_id','User.pseudo',$this->modelClass.'.selling_id',$this->modelClass.'.mode',$this->modelClass.'.price', $this->modelClass.'.seller_id', 'Seller.pseudo', $this->modelClass.'.created'),
      'recursive' => 1
    );
    $this->DataTable->mDataProp = true;

    $response = $this->DataTable->getResponse();

    $this->response->body(json_encode($response));
  }

  public function admin_infos() {
    if (!$this->isConnected || !$this->Permissions->can('PLAYERMARKET__EDIT_STATE'))
      throw new ForbiddenException();
    $this->layout = 'admin';
    $this->set('title_for_layout', 'Gérer le webmarket');

    // get state
    $this->loadModel('PlayerMarket.MinecraftItem');
    $this->set('state', ($this->MinecraftItem->find('count', array('conditions' => array('minecraft_id' => '-1', 'name' => 'DISABLED'))) === 0));
  }

  public function admin_enable() {
    if (!$this->isConnected || !$this->Permissions->can('PLAYERMARKET__EDIT_STATE'))
      throw new ForbiddenException();
    $this->autoRender = false;

    $this->loadModel('PlayerMarket.MinecraftItem');
    $this->MinecraftItem->deleteAll(array('minecraft_id' => -1, 'name' => 'DISABLED'));

    $this->Session->setFlash('Vous avez bien activé le market !', 'default.success');
    $this->redirect(array('action' => 'infos'));
  }
  public function admin_disable() {
    if (!$this->isConnected || !$this->Permissions->can('PLAYERMARKET__EDIT_STATE'))
      throw new ForbiddenException();
      $this->autoRender = false;

    $this->loadModel('PlayerMarket.MinecraftItem');
    $this->MinecraftItem->create();
    $this->MinecraftItem->set(array('minecraft_id' => -1, 'name' => 'DISABLED'));
    $this->MinecraftItem->save();

    $this->Session->setFlash('Vous avez bien désactivé le market !', 'default.success');
    $this->redirect(array('action' => 'infos'));
  }

  /*public function admin_items_refresh() {
    $this->autoRender = false;
    if (!$this->isConnected || !$this->Permissions->can('PLAYERMARKET__EDIT_ITEMS'))
      throw new ForbiddenException();
    $this->loadModel('PlayerMarket.MinecraftItem');
    $this->MinecraftItem->__parse();
    $this->response->body('OK.');
  }*/

}
