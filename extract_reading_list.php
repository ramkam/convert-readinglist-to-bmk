<?php
/*
	Extracts the reading list and saves it into the bookmarks
*/

require_once('config.php');
	# PARAMETERS @ config.php
	# 	$serverIp 
	#	$plistFile
	# ##

# Convert First - compat issues => convert to xml first
$cmd = "plutil -convert xml1 '$plistFile' -o '$plistFile.xml'";
exec($cmd);

# Fix bad chars first
$fileData = file_get_contents("$plistFile.xml");
$fileData = stripInvalidXml($fileData);
	// $xml = simplexml_load_file("$plistFile.xml");



# Load to xml
$xml = simplexml_load_string($fileData);


$obj = $xml->dict->array->dict[3]->array->dict;
	# [3] is the reading list ... because we know ... case by case...

/* Fails
// Put the data in an array & reverse it
$data = Array();
foreach( $obj as $item ) {
	$itemNormalized = normalize_item( $item );
	$data[] = $itemNormalized;
}
$data_reversed = array_reverse($data);
*/

$records = Array();

# Process the array
foreach( $obj as $item ) {
	
	$itemNormalized = normalize_item_that_has_icloud_sync( $item );
		# Normalizing function depends on the version of safari, 2 cases so far :
		#	-> Safari @ Lion
		# 			- When using icloud sync
		#   		- When not using icloud sync
		# ##

/*
	echo "Adding : "  . $itemNormalized['title'] . " : " . $itemNormalized['url'] . "\n";
	$z = add_to_bmkPlusPlus( $itemNormalized ) ;
*/

	$records[] = $itemNormalized;

}

# Sort the array
function array_compare_function($a, $b) {
    return strcmp($a["date"], $b["date"]);
}
usort($records, "array_compare_function");


# Save it to bmk++
foreach ($records as $itemNormalized) {
	// DEBUG : echo $itemNormalized['date'] . ';' .  $itemNormalized['title'] . "; " .  $itemNormalized['url'] . "\n";
	echo "Adding : " . $itemNormalized['date'] . " - " . $itemNormalized['title'] . " : " . $itemNormalized['url'] . "\n";
	$z = add_to_bmkPlusPlus( $itemNormalized ) ;	
}


# ---------------------------------------------------------------------------------------------------------
# FUNCTIONS
#	normalize_item( $item )
#	normalize_item_that_has_icloud_sync( $item )
#	add_to_bmkPlusPlus
#	curl_just_touch
#	
# ---------------------------------------------------------------------------------------------------------

function normalize_item( $item ) {
	
	$r = Array (
		'date' 		=> (string) $item->dict[0]->date[0],	// Date created @ [0]
		'notes' 	=> (string) $item->dict[0]->string,
		'title' 	=> (string) $item->dict[2]->string,
		'url' 	=> (string) $item->string
	);
	
	return $r;
}

function normalize_item_that_has_icloud_sync( $item ) {
	
	$r = Array (
		'date' 		=> (string) $item->dict[0]->date[0],	// Date added @ [0]
		'serverKey' => (string) $item->dict[2]->string[0],	// ... when using icloud
		'serverID' 	=> (string) $item->dict[2]->string[1],	// ... when using icloud
		'title' 	=> (string) $item->dict[3]->string,		// ... when using icloud

		'notes' 	=> (string) $item->dict[0]->string . "(added: " . (string) $item->dict[0]->date[0] . ")" ,		// PreviewText ... optional
		
		'url' 	=> (string) $item->string[0],
		'urlUUID' 	=> (string) $item->string[2],

	);
	
	return $r;
}

function add_to_bmkPlusPlus( $itemNormalized ) {
	global $serverIp;
	
	$url = urlencode( $itemNormalized['url'] );
	$title = urlencode( $itemNormalized['title'] );
	$notes = urlencode( $itemNormalized['notes'] );
	
	$bmkUrl = "http://$serverIp/bmk2/comms/addsimplified.php?token=$token&url=$url&title=$title&js=no&notes=$notes";
	
	# Will produce warnings (because of the redirection ... ), but it works.
	echo "\nCalling:\n$bmkUrl\n";
	// file_get_contents($bmkUrl);
	if ( curl_just_touch($bmkUrl) ) {
		echo " - OK, Added.\n";	
	} else {
		echo " - ##### ERROR #####\n";	
	}
	echo "------ \n";
	
	return "$bmkUrl";
	
}

function curl_just_touch( $bmkUrl ) {
	# We just need to touch the server, it'll add the data
	$ch = curl_init( $bmkUrl );
	curl_setopt($ch, CURLOPT_HEADER, 0);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
	$check = curl_exec($ch);
	curl_close($ch);

	return $check; # false => a problem happenned

}


/*

update bookmarks, domains
set bookmarks.domain = domains.domain_calc
where id = domain_mid and domain is null;

*/



# FRom http://stackoverflow.com/questions/3466035/how-to-skip-invalid-characters-in-xml-file-using-php
# Following from http://stackoverflow.com/questions/14463573/php-simplexml-load-file-invalid-character-error

/**
 * Removes invalid XML
 *
 * @access public
 * @param string $value
 * @return string
 */
function stripInvalidXml($value)
{
    $ret = "";
    $current;
    if (empty($value)) 
    {
        return $ret;
    }

    $length = strlen($value);
    for ($i=0; $i < $length; $i++)
    {
        $current = ord($value{$i});
        if (($current == 0x9) ||
            ($current == 0xA) ||
            ($current == 0xD) ||
            (($current >= 0x20) && ($current <= 0xD7FF)) ||
            (($current >= 0xE000) && ($current <= 0xFFFD)) ||
            (($current >= 0x10000) && ($current <= 0x10FFFF)))
        {
            $ret .= chr($current);
        }
        else
        {
            $ret .= " ";
        }
    }
    return $ret;
}

?>