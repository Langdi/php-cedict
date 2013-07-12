php-cedict
==========

PHP script to process the CC-CEDICT dictionary.
This script converts the CC-CEDICT dictionary into a MySQL table. However it can be easily used for other formats as well (check the foreach loop at the end.)


What data is proccessed?
-------
What information is stored in the entries can be looked up at the CC-CEDICT Wiki Page: http://cc-cedict.org/wiki/format:syntax

This script outputs:
- Traditional Hanzi
- Simplified Hanzi
- Pinyin with tone numbers (i.e. ni3 hao3)
- Pinyin with tone marks (i.e. nị̌ hǎo)
- (English) translation

How to use?
--------
1. Download cedict2mysql.php
2. Download and unzip the dictionary file. The dictionary can be found at: http://www.mdbg.net/chindict/chindict.php?page=cc-cedict
3. Run the script: 
php ./cedict2mysql.php cedict_ts.u8 dictionary > install.sql

4. Import the SQL
5. Done. Have fun. :)
