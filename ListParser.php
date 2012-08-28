<?php


/**
 * Parser for Google SpreadSheets API list feed.
 * 
 * Consumes XML tokens stream and calls own onEntry method on each entry
 * parsed.
 * 
 * Extend it with your own class and override onEntry to process entries.
 */


require_once 'IConsumer.php';

class ListParser implements IConsumer {
	protected $state = 'wait';
	protected $currentEntry = array();
	protected $currentField;
	
	public function data($sax, $data) {
		if(null !== $this->currentField) {
			$fieldName = substr($this->currentField, 4);

			if(!isset($this->currentEntry[$fieldName])) {
				$this->currentEntry[$fieldName] = '';
			}

			$this->currentEntry[$fieldName] .= $data;
		}
	}

	public function start($sax, $tag, $attrs) {
		switch ($this->state) {
			case 'wait':
				if ($tag === 'entry') {
					$this->state = 'entry';
					$this->currentEntry = array();
					return;
				}
			break;
			case 'entry':
				if(0 === strpos($tag, 'gsx:')) {
					$this->currentField = $tag; 
				}
			break;
		}
	}

	public function end($sax, $tag) {
		switch ($this->state) {
			case 'entry':
				if ($tag === 'entry') {
					$this->state = 'wait';
					$this->onEntry($this->currentEntry);
					return;
				}
				if(0 === strpos($tag, 'gsx:')) {
					$this->currentField = null; 
				}
			break;
		}
	}
	
	public function onEntry() {
		
	}
} 
