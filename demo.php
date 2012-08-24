<?php
/**
 * Example of GReader usage.
 * Will pull spreadsheet list feed and process XML while reading
 * HTTP stream.
 */
require_once('GReader.php');
require_once('ListParser.php');

//Extend ListParser and override onEntry with dumping method
class ListDumper extends ListParser {
	public function onEntry($entry) {
		var_dump($entry);
	}
}

$dumper = new ListDumper;
$gr = new GReader('myemail@gmail.com', 'my-secret-P455w0rD', $dumper);
$gr->request('https://spreadsheets.google.com/feeds/list/0AlVieI2VHtbMdGh4Z0prS3RDR20wcWZFUC1BT21HRGc/1/private/full', 'get');

