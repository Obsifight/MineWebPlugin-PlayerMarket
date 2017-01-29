<?php
class Sale extends PlayerMarketAppModel {

  public $useTable = false;
  private $__connectionInited = false;
  private $usersByUUIDs = array();

  public function beforeFind($query) {
    if (!$this->__connectionInited) {
      App::uses('ConnectionManager', 'Model');
      $con = new ConnectionManager;
      ConnectionManager::create('WebMarket', Configure::read('PlayerMarket.config.db'));
      $this->useDbConfig = 'WebMarket';
      $this->useTable = 'webmarket';
      $this->__connectionInited = true;
      $this->MinecraftItem = ClassRegistry::init('PlayerMarket.MinecraftItem');
    }
    return true;
  }

  private function __parseMinecraftColors($string) {
    require_once ROOT.DS.'app'.DS.'Plugin'.DS.'PlayerMarket'.DS.'Vendor'.DS.'Spirit55555'.DS.'MinecraftColors.php';
    $class = new Spirit55555\Minecraft\MinecraftColors();
    return $class->convertToHTML($string);
  }

  private function __getTexturePath($iconId) {
    // icon name
    $item = $this->MinecraftItem->find('first', array('conditions' => array('minecraft_id' => $iconId)));
    $iconName = (!empty($item)) ? $item['MinecraftItem']['texture_name'] : 'null';
    // find
    $pathFind = ROOT.DS.'app'.DS.'Plugin'.DS.'PlayerMarket'.DS.'webroot'.DS.'img'.DS.'textures'.DS.'*'.DS.$iconName.'.png';
    $paths = glob($pathFind);
    $path = (!empty($paths)) ? $paths[0] : null;
    if (empty($path))
      return null;
    // to user access
    $path = explode(ROOT.DS.'app'.DS.'Plugin'.DS.'PlayerMarket'.DS.'webroot', $path)[1];
    return '/PlayerMarket'.$path;
  }

  private function __parse($item) {
    $item['items'] = $this->__parseContent($item['content']);
    unset($item['content']);
    $item['start_of_sale'] = date('Y-m-d H:i:s', round($item['start_of_sale'] / 1000));
    $item['end_of_sale'] = (!empty($item['end_of_sale'])) ? date('Y-m-d H:i:s', round($item['end_of_sale'] / 1000)) : null;
    $item['icon'] = $this->__parseIcon($item['icon']);
    $item['seller'] = $this->__getUsername($item['seller']);
    $item['icon_texture_path'] = $this->__getTexturePath($item['icon']);

    return $item;
  }

  private function __parseIcon($icon) {
    $icon = new SimpleXMLElement($icon);
    return (int)$icon->Item['typeId'];
  }

  private function __getUsername($uuid) {
    if (!isset($this->usersByUUIDs[$uuid])) {
      $result = $this->apiComponent->get('/user/from/uuid/' . $uuid);
      if (!$result->status || !$result->success)
        return false;
      $this->usersByUUIDs[$uuid] = $result->body['username'];
    }
    return $this->usersByUUIDs[$uuid];
  }

  private function __getUUID($username) {
    $result = $this->apiComponent->get('/user/uuid/from/' . $username);
    if (!$result->status || !$result->success)
      return false;
    return $result->body['uuid'];
  }

  private function __translateName($id) {
    $item = $this->MinecraftItem->find('first', array('conditions' => array('minecraft_id' => $id)));
    return (!empty($item)) ? $item['MinecraftItem']['translated_name'] : 'N/A';
  }

  private function __translateEnchantName($name) {
    if (!isset($this->enchantTranslateFile))
      $this->enchantTranslateFile = json_decode(file_get_contents(ROOT.DS.'app'.DS.'Plugin'.DS.'PlayerMarket'.DS.'Vendor'.DS.'Minecraft'.DS.'enchants.json'), true);
    return (isset($this->enchantTranslateFile[$name])) ? $this->enchantTranslateFile[$name] : $name;
  }

  private function __parseContent($content) {
    $items = new SimpleXMLElement($content);
    $result = array();

    $i = 0;
    foreach ($items as $item) {
      // global content
      $result[$i] = array(
        'item_id' => (int)$item['typeId'],
        'amount' => (int)$item->Amount,
        'durability' => @(((int)$item->DurabilityMax - (int)$item->Durability) / (int)$item->DurabilityMax) * 100, // percentage
        'name' => (isset($item->CustomName)) ? $this->__parseMinecraftColors($item->CustomName) : $this->__translateName((int)$item['typeId']),
        'data_id' => (int)$item->Data,
        'enchantments' => array()
      );

      // enchantments
      if (!empty($item->Enchants)) {
        foreach ($item->Enchants as $enchant) {
          array_push($result[$i]['enchantments'], array(
            'name' => $this->__translateEnchantName((string)$enchant->Enchant['name']),
            'level' => (int)$enchant->Enchant['value']
          ));
        }
      }
      $i++;
    }

    return $result;
  }

  public function getAll() {
    $query = $this->find('all', array('conditions' => array('state' => 'PENDING')));
    return array_map(function ($item) {
      return $this->__parse($item['Sale']);
    }, $query);
  }

  public function getFrom($username) {
    $query = $this->find('all', array('conditions' => array('state' => 'PENDING', 'seller' => $this->__getUUID($username))));
    return array_map(function ($item) {
      return $this->__parse($item['Sale']);
    }, $query);
  }
}
