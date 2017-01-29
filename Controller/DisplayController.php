<?php
class DisplayController extends PlayerMarketAppController {

  public function beforeFilter() {
    parent::beforeFilter();
    //$this->serverId = Configure::read('ObsiPlugin.server.pvp.id');
    $this->serverId = 6;
  }

  public function index() {
    $this->set('title_for_layout', $this->Lang->get('PLAYERMARKET__HOMEPAGE_TITLE'));

    $this->loadModel('PlayerMarket.Sale');
    $this->Sale->apiComponent = $this->Components->load('Obsi.Api');
    $this->set('sales', $this->Sale->getAll());
    $this->set('mySales', $this->Sale->getFrom($this->User->getKey('pseudo')));
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

  public function admin_items() {
    if (!$this->isConnected || !$this->Permissions->can('PLAYERMARKET__EDIT_ITEMS'))
      throw new ForbiddenException();
    $this->layout = 'admin';
    $this->set('title_for_layout', 'Éditer les items');

    $this->loadModel('PlayerMarket.MinecraftItem');
    $this->MinecraftItem->__parse();
  }

}
