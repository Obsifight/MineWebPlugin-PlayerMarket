<?php
class PurchaseController extends PlayerMarketAppController {

  public function beforeFilter() {
    parent::beforeFilter();
    $this->serverId = Configure::read('ObsiPlugin.server.pvp.id');
    //$this->serverId = 6;
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

    // Calculate new sold
    $findUser = $this->User->find('first', array('conditions' => array('id' => $this->User->getKey('id'))));
    $newSold = floatval($findUser['User']['money']) - floatval($find['Sale']['price_point']);
    if ($newSold <= 0)
      return $this->response->body(json_encode(array('status' => false, 'msg' => "Vous n'avez pas les fonds nécessaires.")));

    // send command
    $callConnected = $this->Server->call(array('isConnected' => $this->User->getKey('pseudo')), true, $this->serverId);
    if (!isset($callConnected['isConnected']) || $callConnected['isConnected'] != "true")
      return $this->response->body(json_encode(array('status' => false, 'msg' => "Vous êtes déconnecté. Il est donc impossible de procéder à un achat.")));
    if (!$this->Server->online($this->serverId))
      return $this->response->body(json_encode(array('status' => false, 'msg' => "Le serveur est temporairement indisponible. Il est donc impossible de procéder à un achat.")));
   $this->Server->call(array('performCommand' => "market give {$this->User->getKey('pseudo')} {$find['Sale']['id_selling']} POINT"), true, $this->serverId);

    sleep(1); // wait plugin db update

    // check if state = COMPLETED
    $find = $this->Sale->find('first', array('conditions' => array('id_selling' => $id, 'state' => 'COMPLETED')));
    if (empty($find)) {
      sleep(2); // wait plugin db update
      $find = $this->Sale->find('first', array('conditions' => array('id_selling' => $id, 'state' => 'COMPLETED')));
      if (empty($find))
        return $this->response->body(json_encode(array('status' => false, 'msg' => "Une erreur est intervenue lors de l'achat. Veuillez rééssayer.")));
    }

    // Set new sold
    $this->User->id = $this->User->getKey('id');
    $this->User->saveField('money', $newSold);

    // add to seller
    $this->api = $this->Components->load('Obsi.Api');
    $getUsername = $this->api->get('/user/from/uuid/' . $find['Sale']['seller']);
    if (!$getUsername->status || !$getUsername->success)
      return $this->response->body(json_encode(array('status' => false, 'msg' => "Une erreur est intervenue lors de l'achat (2). Veuillez rééssayer.")));
    $username = $getUsername->body['username'];

    // Calculate new sold
    $findUser = $this->User->find('first', array('conditions' => array('pseudo' => $username)));
    $newSold = floatval($findUser['User']['money']) + floatval($find['Sale']['price_point']);

    // Set new sold
    $this->User->id = $findUser['User']['id'];
    $this->User->saveField('money', $newSold);

    // save history
    $this->loadModel('PlayerMarket.PurchaseHistory');
    $this->PurchaseHistory->create();
    $this->PurchaseHistory->set(array(
      'user_id' => $this->User->getKey('id'),
      'selling_id' => $id,
      'mode' => 'POINT',
      'price' => $find['Sale']['price_point'],
      'seller_uuid' => $find['Sale']['seller'],
      'seller_id' => $findUser['User']['id'],
    ));
    $this->PurchaseHistory->save();

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

    sleep(1); // wait plugin db update

    // check if state = COMPLETED
    $find = $this->Sale->find('first', array('conditions' => array('id_selling' => $id, 'state' => 'COMPLETED')));
    if (empty($find))
      return $this->response->body(json_encode(array('status' => false, 'msg' => "Une erreur est intervenue lors de l'achat. Veuillez rééssayer.")));

    // seller id
    $this->api = $this->Components->load('Obsi.Api');
    $getUsername = $this->api->get('/user/from/uuid/' . $find['Sale']['seller']);
    if (!$getUsername->status || !$getUsername->success)
      return $this->response->body(json_encode(array('status' => false, 'msg' => "Une erreur est intervenue lors de l'achat (2). Veuillez rééssayer.")));
    $username = $getUsername->body['username'];
    $findUser = $this->User->find('first', array('conditions' => array('pseudo' => $username)));

    // save history
    $this->loadModel('PlayerMarket.PurchaseHistory');
    $this->PurchaseHistory->create();
    $this->PurchaseHistory->set(array(
      'user_id' => $this->User->getKey('id'),
      'selling_id' => $id,
      'mode' => 'MONEY',
      'price' => $find['Sale']['price_money'],
      'seller_uuid' => $find['Sale']['seller'],
      'seller_id' => $findUser['User']['id'],
    ));
    $this->PurchaseHistory->save();

    // success message
    return $this->response->body(json_encode(array('status' => true)));
  }

  public function recovery() {
    $this->response->type('json');
    $this->autoRender = false;

    if (!$this->isConnected)
      throw new ForbiddenException('Not logged');
    if (empty($this->request->params['id']))
      throw new NotFoundException('Missing id');
    $id = $this->request->params['id'];

    // uuid for find
    $this->api = $this->Components->load('Obsi.Api');
    $getUUID = $this->api->get('/user/uuid/from/' . $this->User->getKey('pseudo'));
    if (!$getUUID->status || !$getUUID->success)
      return false;
    $uuid = $getUUID->body['uuid'];

    // find
    $this->loadModel('PlayerMarket.Sale');
    $find = $this->Sale->find('first', array('conditions' => array('id_selling' => $id, 'state' => 'PENDING', 'seller' => $uuid)));
    if (empty($find))
      throw new NotFoundException('Sale not found');

    // check date
    if (strtotime('+48 hours', strtotime($find['Sale']['start_of_sale'])) > time())
      throw new NotFoundException('Not 48 hours');

    // send command
    $callConnected = $this->Server->call(array('isConnected' => $this->User->getKey('pseudo')), true, $this->serverId);
    if (!isset($callConnected['isConnected']) || $callConnected['isConnected'] != "true")
      return $this->response->body(json_encode(array('status' => false, 'msg' => "Vous êtes déconnecté. Il est donc impossible de procéder à cette opération.")));
    if (!$this->Server->online($this->serverId))
      return $this->response->body(json_encode(array('status' => false, 'msg' => "Le serveur est temporairement indisponible. Il est donc impossible de procéder à cette opération.")));
    $this->Server->call(array('performCommand' => "market give {$this->User->getKey('pseudo')} {$find['Sale']['id_selling']} RECOVERY"), true, $this->serverId);

    sleep(1); // wait plugin db update

    // check if state = RECOVERY
    $find = $this->Sale->find('first', array('conditions' => array('id_selling' => $id, 'state' => 'RECOVERY')));
    if (empty($find))
      return $this->response->body(json_encode(array('status' => false, 'msg' => "Une erreur est intervenue lors de l'opération. Veuillez rééssayer.")));

    // save history
    $this->loadModel('PlayerMarket.PurchaseHistory');
    $this->PurchaseHistory->create();
    $this->PurchaseHistory->set(array(
      'user_id' => $this->User->getKey('id'),
      'selling_id' => $id,
      'mode' => 'RECOVERY',
      'price' => 0.0,
      'seller_uuid' => $find['Sale']['seller'],
      'seller_id' => $this->User->getKey('id'),
    ));
    $this->PurchaseHistory->save();

    // success message
    return $this->response->body(json_encode(array('status' => true)));
  }

}
