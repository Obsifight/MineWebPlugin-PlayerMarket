<?php
class Sale extends PlayerMarketAppModel {

  public $useTable = false;
  private $__connectionInited = false;

  public function beforeFind($query) {
    if (!$this->__connectionInited) {
      App::uses('ConnectionManager', 'Model');
      $con = new ConnectionManager;
      ConnectionManager::create('WebMarket', Configure::read('PlayerMarket.config.db'));
      $this->useDbConfig = 'WebMarket';
      $this->useTable = 'webmarket';
      $this->__connectionInited = true;
    }
    return true;
  }

  private function __parseMinecraftColors($string) {
    require_once ROOT.DS.'app'.DS.'Plugin'.DS.'PlayerMarket'.DS.'Vendor'.DS.'Spirit55555'.DS.'MinecraftColors.php';
    $class = new Spirit55555\Minecraft\MinecraftColors();
    return $class->convertToHTML($string);
  }

  private function __getTexturePath($icon) {
    // icon name
    $iconName = strtolower($icon);
    if ($iconName == 'wool')
      $iconName = 'wool_colored_white';
    if ($iconName == 'log')
      $iconName = 'log_oak';
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
    $item['icon'] = explode(';', $item['icon'])[0];
    $item['seller'] = $this->__getUsername($item['seller']);
    $item['icon_texture_path'] = $this->__getTexturePath($item['icon']);

    return $item;
  }

  private function __getUsername($uuid) {
    $result = $this->apiComponent->get('/user/from/uuid/' . $uuid);
    if (!$result->status || !$result->success)
      return false;
    return $result->body['username'];
  }

  private function __parseContent($content) {
    $items = explode('|', $content);
    $result = array();

    $i = 0;
    foreach ($items as $item) {
      // NAME;AMOUNT;DURABILITY;NOM_CUSTOM;DATA_ID;ENCHANT_1:NIVEAU,ENCHANT_2:NIVEAU
      $item = explode(';', $item);

      $translatedName = $item[0];

      // global content
      $result[$i] = array(
        'name' => $item[0],
        'amount' => $item[1],
        'durability' => $item[2],
        'custom_name' => (!empty($item[3])) ? $this->__parseMinecraftColors($item[3]) : $translatedName,
        'data_id' => $item[4],
        'enchantments' => array()
      );

      // enchantments
      $enchantments = explode(',', $item[5]);
      if (!empty($enchantments)) {
        foreach ($enchantments as $enchant) {
          if (!empty($enchant)) {
            $enchant = explode(':', $enchant);
            $enchantName = $enchant[0];
            $enchantLevel = $enchant[1];
            array_push($result[$i]['enchantments'], array(
              'name' => $enchantName,
              'level' => $enchantLevel
            ));
          }
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
}
