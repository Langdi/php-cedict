<?php
/**
 * User: Daniel Lang
 * Date: 12.07.13
 * Time: 22:29
 */

$dictionary = file("cedict_ts-utf8.dict");
$randomEntry = array_rand($dictionary);

echo "Loading dictionary used up " . (memory_get_usage(true) / (1024 * 1024)) . " MB\n";
echo "Random entry: " . $dictionary[$randomEntry] . "\n";