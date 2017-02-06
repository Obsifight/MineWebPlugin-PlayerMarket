<?php
class PurchaseHistory extends PlayerMarketAppModel {
  public $belongsTo = array(
    'User',
    'Seller' => array(
      'className' => 'User',
      'foreignKey' => 'seller_id'
    )
  );
}
