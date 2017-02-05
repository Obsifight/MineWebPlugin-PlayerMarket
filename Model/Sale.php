<?php
class Sale extends PlayerMarketAppModel {

  public $useTable = false;
  private $__connectionInited = false;
  private $usersByUUIDs = array();
  public $tablePrefix = false;

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
    return '<span>' . htmlentities($string) . '</span>';
    /*require_once ROOT.DS.'app'.DS.'Plugin'.DS.'PlayerMarket'.DS.'Vendor'.DS.'Spirit55555'.DS.'MinecraftColors.php';
    $class = new Spirit55555\Minecraft\MinecraftColors();
    return $class->convertToHTML($string);*/
  }

  private function __globRecursive($pattern, $flags = 0) {
    $files = glob($pattern, $flags);
    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
      $files = array_merge($files, $this->__globRecursive($dir.'/'.basename($pattern), $flags));
    }
    return $files;
  }

  private function __getTexturePath($iconId, $dataId = false) {
    // icon name
    if ($dataId && $dataId > 0)
      $iconIdWithData = $iconId . ':' . $dataId;
    else
      $iconIdWithData = $iconId;
    $item = $this->MinecraftItem->find('first', array('conditions' => array('minecraft_id' => $iconIdWithData)));
    if (empty($item)) // search without data_id
      $item = $this->MinecraftItem->find('first', array('conditions' => array('minecraft_id' => $iconId)));
    $iconName = (!empty($item)) ? $item['MinecraftItem']['texture_name'] : 'null';
    // find
    $pathFind = ROOT.DS.'app'.DS.'Plugin'.DS.'PlayerMarket'.DS.'webroot'.DS.'img'.DS.'textures'.DS.'*'.DS.$iconName.'.png';
    $paths = $this->__globRecursive($pathFind);
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
    $item['seller'] = $item['seller'];
    $item['icon_texture_path'] = $this->__getTexturePath($item['icon']);

    return $item;
  }

  private function __parseIcon($icon) {
    $icon = new SimpleXMLElement($icon);
    return (int)$icon->Item['typeId'];
  }

  private function __getUUID($username) {
    $result = $this->apiComponent->get('/user/uuid/from/' . $username);
    if (!$result->status || !$result->success)
      return false;
    return $result->body['uuid'];
  }

  private function __translateName($id, $dataId = false) {
    if ($dataId && $dataId > 0)
      $idWidthData = $id . ':' . $dataId;
    else
      $idWidthData = $id;
    $item = $this->MinecraftItem->find('first', array('conditions' => array('minecraft_id' => $idWidthData)));
    if (empty($item)) // search without data
      $item = $this->MinecraftItem->find('first', array('conditions' => array('minecraft_id' => $id)));
    return (!empty($item)) ? $item['MinecraftItem']['translated_name'] : 'N/A';
  }

  private function __translatePotionName($item) {
    if (!isset($item->Potion))
      return 'Potion';
    // name (splash/force/effect)
    $name = 'Potion de ';
    switch ((string)$item->Potion['effect']) {
      case 'INCREASE_DAMAGE':
        $name .= 'Force';
        break;
      case 'SLOW':
        $name .= 'Ralentissement';
        break;
      case 'HARM':
        $name .= 'Dégats instantanés';
        break;
      case 'WATER_BREATHING':
        $name .= 'Respiration';
        break;
      case 'SPEED':
        $name .= 'Vitesse';
        break;
      case 'FIRE_RESISTANCE':
        $name .= 'Résistance au feu';
        break;
      case 'INVISIBILITY':
        $name .= 'Invisibilité';
        break;
      case 'HEAL':
        $name .= 'Vie instantanée';
        break;
      case 'NIGHT_VISION':
        $name .= 'Vision nocture';
        break;
      case 'POISON':
        $name .= 'Poison';
        break;
      case 'POISON':
        $name .= 'Poison';
        break;
      case 'REGENERATION':
        $name .= 'Régénération';
        break;
      case 'WEAKNESS':
        $name .= 'Faiblesse';
        break;
      case 'INCREASE_DAMAGE':
        $name .= 'Force';
        break;
      case 'NOBACK':
        $name .= 'No Back';
        break;

      default:
        $name .= ucfirst(strtolower((string)$item->Potion['effect']));
        break;
    }
    $name .= ((bool)$item->Potion['splash']) ? ' Splash' : '';
    $name .= ((int)$item->Potion['effect'] === 2) ? ' II' : ' I';
    // duration
    $duration = (float)$item->Potion['duration'];
    if ($duration > 0) {
      if ($duration >= 60) { // to minutes + seconds
        // calcul
        $minutes = $duration / 60;
        $decimal = $minutes;
        $minutes = (int)$minutes;
        $seconds = ($decimal - $minutes) * 60;
        // display
        if ($seconds < 10)
          $seconds = '0' . $seconds;
      } else {
        $minutes = 0;
        $seconds = $duration;
      }
      $duration = $minutes . ':' . $seconds;
      $name .= ' (' . $duration . ')';
    }
    return $name;
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
      // name
      if ((int)$item['typeId'] === 373)
        $name = $this->__translatePotionName($item);
      else if (isset($item->CustomName))
        $name = $this->__parseMinecraftColors($item->CustomName);
      else
        $name = $this->__translateName((int)$item['typeId'], (int)$item->Data);
      // global content
      $result[$i] = array(
        'item_id' => (int)$item['typeId'],
        'amount' => (int)$item->Amount,
        'current_durability' => (int)$item->Durability,
        'max_durability' => (int)$item->DurabilityMax,
        'durability' => @(((int)$item->DurabilityMax - (int)$item->Durability) / (int)$item->DurabilityMax) * 100, // percentage
        'name' => $name,
        'data_id' => (int)$item->Data,
        'img_path' => $this->__getTexturePath((int)$item['typeId'], (int)$item->Data),
        'enchantments' => array()
      );
      $result[$i]['name'] = str_replace("'", '', $result[$i]['name']);

      // enchantments
      if (!empty($item->Enchants)) {
        foreach ($item->Enchants->Enchant as $enchant) {
          array_push($result[$i]['enchantments'], array(
            'name' => $this->__translateEnchantName((string)$enchant['name']),
            'level' => (int)$enchant['value']
          ));
        }
      }
      $i++;
    }

    return $result;
  }

  public function getAll() {
    $query = $this->find('all', array('conditions' => array('state' => 'PENDING', 'DATE_ADD(FROM_UNIXTIME(`start_of_sale` * 0.001), INTERVAL 72 HOUR) > NOW()')));
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
