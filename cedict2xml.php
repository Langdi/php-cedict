<?php
/**
*
*	Author: Matt Guerrette
*	Date: 	4/20/2014
*	Github:	https://github.com/MattGuerrette 	
*	
*	Purpose: This is an edited version of Langdi's cedict2mysql php script
			 that creates an xml file
*
*/

/**
* Credits for this function go to velcrow, who shared this
* at http://stackoverflow.com/questions/1162491/alternative-to-mysql-real-escape-string-without-connecting-to-db
* @param string $string the string to be escaped
* @return string the escaped string
*/
function escape($value)
{
    $search = array("\\", "\x00", "\n", "\r", "'", '"', "\x1a");
    $replace = array("\\\\", "\\0", "\\n", "\\r", "\'", '\"', "\\Z");

    return str_replace($search, $replace, $value);
}

/**
* Credits for these 2 functions go to Bouke Versteegh, who shared these
* at http://stackoverflow.com/questions/1598856/convert-numbered-to-accentuated-pinyin
*
* @param string $string The pinyin string with tone numbers, i.e. "ni3 hao3"
* @return string The formatted string with tone marks, i.e.
*/
function pinyin_addaccents($string)
{
    # Find words with a number behind them, and replace with callback fn.
    return preg_replace_callback(
        '~([a-zA-ZüÜ]+)(\d)~',
        'pinyin_addaccents_cb',
        $string);
}

/**
* Credits for this function goes to Langdi, the original autho of cedict2mysql
*/
# Helper callback
function pinyin_addaccents_cb($match)
{
    static $accentmap = null;

    if ($accentmap === null) {
        # Where to place the accent marks
        $stars =
            'a* e* i* o* u* ü* ' .
                'A* E* I* O* U* Ü* ' .
                'a*i a*o e*i ia* ia*o ie* io* iu* ' .
                'A*I A*O E*I IA* IA*O IE* IO* IU* ' .
                'o*u ua* ua*i ue* ui* uo* üe* ' .
                'O*U UA* UA*I UE* UI* UO* ÜE*';
        $nostars = str_replace('*', '', $stars);

        # Build an array like Array('a' => 'a*') and store statically
        $accentmap = array_combine(explode(' ', $nostars), explode(' ', $stars));
        unset($stars, $nostars);
    }

    static $vowels =
    Array('a*', 'e*', 'i*', 'o*', 'u*', 'ü*', 'A*', 'E*', 'I*', 'O*', 'U*', 'Ü*');

    static $pinyin = Array(
        1 => Array('a', 'e', 'i', 'o', 'u', 'u', 'A', 'E', 'I', 'O', 'U', 'U'),
        2 => Array('á', 'é', 'í', 'ó', 'ú', 'u', 'Á', 'É', 'Í', 'Ó', 'Ú', 'U'),
        3 => Array('a', 'e', 'i', 'o', 'u', 'u', 'A', 'E', 'I', 'O', 'U', 'U'),
        4 => Array('à', 'è', 'ì', 'ò', 'ù', 'u', 'À', 'È', 'Ì', 'Ò', 'Ù', 'U'),
        5 => Array('a', 'e', 'i', 'o', 'u', 'ü', 'A', 'E', 'I', 'O', 'U', 'Ü')
    );

    list(, $word, $tone) = $match;
    # Add star to vowelcluster
    $word = strtr($word, $accentmap);
    # Replace starred letter with accented
    $word = str_replace($vowels, $pinyin[$tone], $word);
    return $word;
}


#edited original usage function
function usage()
{
    global $argv;
    $text = "Generates an XML file for the CC-CEDICT dictionary. For the xml format, look up the readme.\n";
    $text .= sprintf("usage: php %s <dictionary-file> [xml filename] \n\n", $argv[0]);

    echo $text;
}

/*
* Check for correct usage of this script
*/
if ($argc < 2 || $argc > 3) {
    usage();
    exit;
}

if (!is_readable($argv[1])) {
    echo sprintf("The specified dictionary file %s is not readable. \n", $argv[1]);
    exit;
}

if (isset($argv[2])) {
    $xml_name = $argv[2];
} else {
    usage();
	exit;
}


$dictionary = file($argv[1]);
$regex = "#(.*?) (.*?) \[(.*?)\] \/(.*)\/#";

/*
/*	Code below generates an XML file
*/
$id = 0;
$entries = array();
foreach($dictionary as $entry)
{
	if(substr($entry, 0, 1) == "#")
	{
		continue;
	}
	preg_match($regex, $entry, $matches);
	
	$traditional = $matches[1];
    $simplified = $matches[2];
    $pinyin_numbers = $matches[3];
    $pinyin_marks = pinyin_addaccents($matches[3]);
    $translation = escape($matches[4]);
	
		$entries [] = array(
		'id' => $id,
		'traditional' => $traditional,
		'simplified' => $simplified,
		'pinyin_numbers' => $pinyin_numbers,
		'pinyin_marks' => $pinyin_marks,
		'translation' => $translation);
	$id++;
}

#create DOM document with UTF-8 encoding
$doc = new DOMDocument('1.0', 'UTF-8');
$doc->formatOutput = true;

$r = $doc->createElement("entries");
$doc->appendChild($r);

foreach($entries as $entry)
{
	$b = $doc->createElement("entry");
	
	$id_num = $doc->createElement("id");
	$id_num->appendChild($doc->createTextNode($entry["id"]));
	$b->appendChild($id_num);
	
	$trad = $doc->createElement("traditional");
	$trad->appendChild($doc->createTextNode($entry["traditional"]));
	$b->appendChild($trad);
	
	$simp = $doc->createElement("simplified");
	$simp->appendChild($doc->createTextNode($entry["simplified"]));
	$b->appendChild($simp);
	
	$pinyin_num = $doc->createElement("pinyin_numbers");
	$pinyin_num->appendChild($doc->createTextNode($entry["pinyin_numbers"]));
	$b->appendChild($pinyin_num);
	
	$pinyin_mark = $doc->createElement("pinyin_marks");
	$pinyin_mark->appendChild($doc->createTextNode($entry["pinyin_marks"]));
	$b->appendChild($pinyin_mark);
	
	$trans = $doc->createElement("translation");
	$trans->appendChild($doc->createTextNode($entry["translation"]));
	$b->appendChild($trans);
	
	$r->appendChild($b);
}

echo $doc->save($xml_name);
?>
