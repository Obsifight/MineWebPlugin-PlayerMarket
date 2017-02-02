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

    $this->loadModel('PlayerMarket.Sale');
    $this->Sale->apiComponent = $this->Components->load('Obsi.Api');
    $this->set('sales', array_map(function ($sale) {
      // display items
      foreach ($sale['items'] as $k => $item) {
        $sale['items'][$k]['name_parsed'] =  $item['amount'].' '.$item['name'];
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

  public function admin_items_refresh() {
    $this->autoRender = false;
    if (!$this->isConnected || !$this->Permissions->can('PLAYERMARKET__EDIT_ITEMS'))
      throw new ForbiddenException();
    $this->loadModel('PlayerMarket.MinecraftItem');
    $this->MinecraftItem->__parse();
    $this->response->body('OK.');
  }

}
