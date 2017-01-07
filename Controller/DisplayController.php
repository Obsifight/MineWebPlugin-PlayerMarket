<?php
class DisplayController extends PlayerMarketAppController {

  public function index() {
    $this->set('title_for_layout', $this->Lang->get('PLAYERMARKET__HOMEPAGE_TITLE'));

    $this->loadModel('PlayerMarket.Sale');
    $this->Sale->apiComponent = $this->Components->load('Obsi.Api');
    $this->set('sales', $this->Sale->getAll());
  }

}
