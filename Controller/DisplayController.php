<?php
class DisplayController extends PlayerMarketAppController {

  public function index() {
    $this->set('title_for_layout', $this->Lang->get('PLAYERMARKET__HOMEPAGE_TITLE'));

    $this->loadModel('PlayerMarket.Sale');
    $this->Sale->apiComponent = $this->Components->load('Obsi.Api');
    $this->set('sales', $this->Sale->getAll());
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
