<?php

/**
 * Interface of XML token stream consumer. Your parser must implement it
 */

interface IConsumer {
	public function start($sax, $tag, $attrs);
	public function end  ($sax, $tag);
	public function data ($sax, $data);
}
