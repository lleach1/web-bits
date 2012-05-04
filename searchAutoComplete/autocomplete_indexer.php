<?php
//make a file  containing the JSON representation of autocomplete search terms

//where we are storing the json encoded index files
$json_dir = '/srv/ingeniux-web/PreBuilt/scripts/json';

//directory for reading the Ingeniux XML files
$xml_dir = '/srv/ingeniux-web';

$xpower_paths = array(
	"its" => "/Content Store/Site Folder/Public Components/Home/Administration/ITS Home"
);

$xids = array(
	"a-z_page" => array("x41451")
);

$dh = opendir($xml_dir);

foreach ($xpower_paths AS $section=>$xpower_path){
	$index_file = $json_dir . '/' . $section . '_index.js';
	touch ($index_file) or die ("could not create/write to " . $index_file);
	
   	$index = array();
    while (false !== ($file = readdir($dh))) {
    	if (substr($file, -4) == ".xml"){
        	$doc = new DOMDocument();
			$doc->loadXML(file_get_contents($xml_dir . '/' . $file));
			$doc_xpath = new DOMXpath($doc);
			$rootnode = $doc_xpath->query('/*[1]');
			if ($rootnode->length){
				$xpowerattr = $rootnode->item(0)->attributes->getNamedItem('XPowerPath');
				if ($xpowerattr){
					if ($xpower_path == substr($xpowerattr->value, 0, strlen($xpower_path))){
						$page_index = array('value' => $file);
						$titlenode = $doc_xpath->query('/*/PageTitle');
						if ($titlenode->length){
							if (strlen(strip_tags(trim($titlenode->item(0)->nodeValue)))){
								$page_index['label'] = $page_index['title'] = strip_tags(trim($titlenode->item(0)->nodeValue));
								$keywordsnode = $doc_xpath->query('/*/MetaKeywords');
								if ($keywordsnode->length){
									if (strlen($keywordsnode->item(0)->nodeValue)){
										$page_index['label'] = $keywordsnode->item(0)->nodeValue . ', ' . $page_index['label'];
									}
								}
								$index[$xpowerattr->value] = $page_index;
							}
						}
						
					}
				}
			}
        }
    }
	$index = array_values($index); //drop xpowerpath keys
  	
  	file_put_contents($index_file, 'jsonAutoCompData(' . my_json_encode($index) . ')');
  	
	rewinddir($dh);
	
}
closedir($dh);

/**
 * turns php variables into JSON strings
 */
function my_json_encode($var) {
  switch (gettype($var)) {
    case 'boolean':
      return $var ? 'true' : 'false'; // Lowercase necessary!
    case 'integer':
    case 'double':
      return $var;
    case 'resource':
    case 'string':
      return '"'. str_replace(array("\r", "\n", "<", ">", "&"),
                              //array('\r', '\n', '\x3c', '\x3e', '\x26'),
                             array('\r', '\n', '\u003C', '\u003E', '\u0026'), 
      						 addslashes($var)) .'"';
    case 'array':
      // Arrays in JSON can't be associative. If the array is empty or if it
      // has sequential whole number keys starting with 0, it's not associative
      // so we can go ahead and convert it as an array.
      if (empty ($var) || array_keys($var) === range(0, sizeof($var) - 1)) {
        $output = array();
        foreach ($var as $v) {
          $output[] = my_json_encode($v);
        }
        return '[ '. implode(', ', $output) .' ]';
      }
      // Otherwise, fall through to convert the array as an object.
    case 'object':
      $output = array();
      foreach ($var as $k => $v) {
        $output[] = my_json_encode(strval($k)) .': '. my_json_encode($v);
      }
      return '{ '. implode(', ', $output) .' }';
    default:
      return 'null';
  }
}
?>
