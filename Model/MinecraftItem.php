<?php
class MinecraftItem extends PlayerMarketAppModel {
  public $useTable = 'playermarket__minecraft_items';

  public function __parse() {
    $this->paths = array(
      'lang' => ROOT.DS.'app'.DS.'Plugin'.DS.'PlayerMarket'.DS.'Vendor'.DS.'Minecraft'.DS.'en_US.lang',
      'blocks' => ROOT.DS.'app'.DS.'Plugin'.DS.'PlayerMarket'.DS.'Vendor'.DS.'Minecraft'.DS.'Block.java',
      'items' => ROOT.DS.'app'.DS.'Plugin'.DS.'PlayerMarket'.DS.'Vendor'.DS.'Minecraft'.DS.'Item.java'
    );
    $results = array();

    /* =========
    	LANG
    ========= */
    $lang = file_get_contents($this->paths['lang']);
    // parse lang file
    $langArray = array();
    foreach (explode("\n", $lang) as $line) {
    	if (!empty($line)) {
    		$lineExploded = explode('=', $line);
        if (!empty($lineExploded[0]) && isset($lineExploded[1]))
    		  $langArray[$lineExploded[0]] = trim($lineExploded[1]);
    	}
    }

    /* =========
    	BLOCKS
    ============ */
    // get all blocks
    $blocksFile = file_get_contents($this->paths['blocks']);
    preg_match_all('/blockRegistry.addObject\([0-9]*.*\.setBlockTextureName\("[A-Z_]*"\)\)/i', $blocksFile, $matchesBlocks);

    // each
    $blocks = array();
    foreach ($matchesBlocks[0] as $match) {
    	// get id
    	preg_match('/\.addObject\([0-9]*,/i', $match, $id);
    	preg_match('/[0-9]*,/i', $id[0], $id);
    	$id = intval(str_replace(',', '', $id[0]));
    	// get names
    	preg_match_all('/"[A-Z_]*"/i', $match, $names);
    	// set
    	$blocks[$id] = array(
    		'name' => str_replace('"', '', str_replace('"', '', $names[0][1])),
    		'texture_name' => (isset($names[0][2])) ? str_replace('"', '', str_replace('"', '', $names[0][2])) : null
    	);
    	$blocks[$id]['unlocalized_name'] = 'tile.'.$blocks[$id]['name'].'.name';

    	// translate
    	$blocks[$id]['translated_name'] = (isset($langArray[$blocks[$id]['unlocalized_name']])) ? $langArray[$blocks[$id]['unlocalized_name']] : null;
    	$results[$id] = $blocks[$id];
    }

    /* =========
    	ITEMS
    ============ */
    // get all items
    $itemsFile = file_get_contents($this->paths['items']);
    preg_match_all('/itemRegistry.addObject\([0-9]*.*\.setTextureName\("[A-Z_]*"\)\)/i', $itemsFile, $matchesItems);

    // each
    $items = array();
    foreach ($matchesItems[0] as $match) {
    	// get id
    	preg_match('/\.addObject\([0-9]*,/i', $match, $id);
    	preg_match('/[0-9]*,/i', $id[0], $id);
    	$id = intval(str_replace(',', '', $id[0]));
    	// get names
    	preg_match_all('/"[A-Z_]*"/i', $match, $names);
    	// set
    	$items[$id] = array(
    		'name' => str_replace('"', '', str_replace('"', '', $names[0][1])),
    		'texture_name' => str_replace('"', '', str_replace('"', '', $names[0][2]))
    	);
    	$items[$id]['unlocalized_name'] = 'item.'.$items[$id]['name'].'.name';

    	// translate
    	$items[$id]['translated_name'] = (isset($langArray[$items[$id]['unlocalized_name']])) ? $langArray[$items[$id]['unlocalized_name']] : null;
    	$results[$id] = $items[$id];
    }

    /* =========
    	SAVE
    ============ */
    $this->saveMany(array_map(function ($k, $v) {
      return array(
        'minecraft_id' => $k,
        'name' => $v['name'],
        'texture_name' => $v['texture_name'],
        'unlocalized_name' => $v['unlocalized_name'],
        'translated_name' => $v['translated_name']
      );
    }, array_keys($results), $results));
  }
}
