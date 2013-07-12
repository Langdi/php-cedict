<?php
/**
 * User: Daniel Lang
 * Date: 12.07.13
 * Time: 22:29
 */


/**
 * Credits for this function go to velcrow, who shared this
 * at http://stackoverflow.com/questions/1162491/alternative-to-mysql-real-escape-string-without-connecting-to-db
 * @param string $string the string to  be escaped
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
        1 => Array('ā', 'ē', 'ī', 'ō', 'ū', 'ǖ', 'Ā', 'Ē', 'Ī', 'Ō', 'Ū', 'Ǖ'),
        2 => Array('á', 'é', 'í', 'ó', 'ú', 'ǘ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ǘ'),
        3 => Array('ǎ', 'ě', 'ǐ', 'ǒ', 'ǔ', 'ǚ', 'Ǎ', 'Ě', 'Ǐ', 'Ǒ', 'Ǔ', 'Ǚ'),
        4 => Array('à', 'è', 'ì', 'ò', 'ù', 'ǜ', 'À', 'È', 'Ì', 'Ò', 'Ù', 'Ǜ'),
        5 => Array('a', 'e', 'i', 'o', 'u', 'ü', 'A', 'E', 'I', 'O', 'U', 'Ü')
    );

    list(, $word, $tone) = $match;
    # Add star to vowelcluster
    $word = strtr($word, $accentmap);
    # Replace starred letter with accented
    $word = str_replace($vowels, $pinyin[$tone], $word);
    return $word;
}

function usage()
{
    global $argv;
    $text = "Generates an MySQL/Maria SQL-Script for the CC-CEDICT dictionary. For the table format, look up the readme.\n";
    $text .= sprintf("usage: php %s <dictionary-file> [tablename] \n\n", $argv[0]);
    $text .= sprintf("Example: \nphp %s cedict_ts.u8 dictionary > install.sql \n", $argv[0]);

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
}

if (isset($argv[2])) {
    $tablename = $argv[2];
} else {
    $tablename = 'dictionary';
}


$dictionary = file($argv[1]);
$regex = "#(.*?) (.*?) \[(.*?)\] \/(.*)\/#";


$sql = "CREATE TABLE IF NOT EXISTS `{$tablename}` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `traditional` varchar(50) NOT NULL,
          `simplified` varchar(50) NOT NULL,
          `pinyin_numbers` varchar(50) NOT NULL,
          `pinyin_marks` varchar(50) NOT NULL,
          `translation` text NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB  DEFAULT CHARSET=utf8; \n\n";

$sql .= "INSERT INTO  `{$tablename}` (
            `traditional` ,
            `simplified` ,
            `pinyin_numbers` ,
            `pinyin_marks` ,
            `translation`
        )
        VALUES
       ";

foreach ($dictionary as $entry) {
    if (substr($entry, 0, 1) == "#") {
        continue;
    }
    preg_match($regex, $entry, $matches);

    $traditional = $matches[1];
    $simplified = $matches[2];
    $pinyin_numbers = $matches[3];
    $pinyin_marks = pinyin_addaccents($matches[3]);
    $translation = escape($matches[4]);

    $value_string = '("' . $traditional . '", "' . $simplified . '", "' . $pinyin_numbers . '", "' . $pinyin_marks . '", "' . $translation . '"),' . "\n";

    $sql .= $value_string;
}
$sql = mb_substr($sql, 0, -2);
$sql .= ";\n";

echo $sql;