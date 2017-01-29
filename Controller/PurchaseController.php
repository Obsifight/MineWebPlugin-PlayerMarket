<?php
class PurchaseController extends PlayerMarketAppController {

  public function beforeFilter() {
    parent::beforeFilter();
    //$this->serverId = Configure::read('ObsiPlugin.server.pvp.id');
    $this->serverId = 6;
  }

  public function buyWithPoints() {
    $this->response->type('json');
    $this->autoRender = false;

    if (!$this->isConnected)
      throw new ForbiddenException('Not logged');
    if (empty($this->request->params['id']))
      throw new NotFoundException('Missing id');
    $id = $this->request->params['id'];

    // find
    $this->loadModel('PlayerMarket.Sale');
    $find = $this->Sale->find('first', array('conditions' => array('id_selling' => $id, 'state' => 'PENDING')));
    if (empty($find))
      throw new NotFoundException('Sale not found');

    // check price
    if ($this->User->getKey('money') < $find['Sale']['price_money'])
      return $this->response->body(json_encode(array('status' => false, 'msg' => "Vous ne disposez pas d'assez de {$this->Configuration->getMoneyName()} pour procéder à cet achat.")));

    // send command
    $callConnected = $this->Server->call(array('isConnected' => $this->User->getKey('pseudo')), true, $this->serverId);
    if (!isset($callConnected['isConnected']) || $callConnected['isConnected'] != "true")
      return $this->response->body(json_encode(array('status' => false, 'msg' => "Vous êtes déconnecté. Il est donc impossible de procéder à un achat.")));
    if (!$this->Server->online($this->serverId))
      return $this->response->body(json_encode(array('status' => false, 'msg' => "Le serveur est temporairement indisponible. Il est donc impossible de procéder à un achat.")));
    $this->Server->call(array('performCommand' => "market give {$this->User->getKey('pseudo')} {$find['Sale']['id_selling']} POINTS"), true, $this->serverId);

    // check if state = COMPLETED
    $find = $this->Sale->find('first', array('conditions' => array('id_selling' => $id, 'state' => 'COMPLETED')));
    if (empty($find))
      return $this->response->body(json_encode(array('status' => false, 'msg' => "Une erreur est intervenue lors de l'achat. Veuillez rééssayer.")));

    // Calculate new sold
    $findUser = $this->User->find('first', array('conditions' => array('id' => $this->User->getKey('id'))));
    $newSold = floatval($findUser['User']['money']) - floatval($find['Sale']['price_money']);

    // Set new sold
    $this->User->id = $this->User->getKey('id');
    $this->User->saveField('money', $newSold);

    // success message
    return $this->response->body(json_encode(array('status' => true)));
  }

  public function buyWithMoney() {
    $this->response->type('json');
    $this->autoRender = false;

    if (!$this->isConnected)
      throw new ForbiddenException('Not logged');
    if (empty($this->request->params['id']))
      throw new NotFoundException('Missing id');
    $id = $this->request->params['id'];

    // find
    $this->loadModel('PlayerMarket.Sale');
    $find = $this->Sale->find('first', array('conditions' => array('id_selling' => $id, 'state' => 'PENDING')));
    if (empty($find))
      throw new NotFoundException('Sale not found');

    // send command
    $callConnected = $this->Server->call(array('isConnected' => $this->User->getKey('pseudo')), true, $this->serverId);
    if (!isset($callConnected['isConnected']) || $callConnected['isConnected'] != "true")
      return $this->response->body(json_encode(array('status' => false, 'msg' => "Vous êtes déconnecté. Il est donc impossible de procéder à un achat.")));
    if (!$this->Server->online($this->serverId))
      return $this->response->body(json_encode(array('status' => false, 'msg' => "Le serveur est temporairement indisponible. Il est donc impossible de procéder à un achat.")));
    $this->Server->call(array('performCommand' => "market give {$this->User->getKey('pseudo')} {$find['Sale']['id_selling']} MONEY"), true, $this->serverId);

    // check if state = COMPLETED
    $find = $this->Sale->find('first', array('conditions' => array('id_selling' => $id, 'state' => 'COMPLETED')));
    if (empty($find))
      return $this->response->body(json_encode(array('status' => false, 'msg' => "Une erreur est intervenue lors de l'achat. Veuillez rééssayer.")));

    // success message
    return $this->response->body(json_encode(array('status' => true)));
  }

}
