<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Layout extends Controller_Xslt 
{
  	public $template = 'layout';

	public function before()
	{
		parent::before();
		
		if ($this->auto_render)
		{
			// Define global vars
			$this->template->kanema		= 'http://www.kanema.com.br/';
			$this->template->title		= '';
			$this->template->content	= '';
			$this->template->media = Array(
				"script" => Array()
			);
		}
      }

	public function after()
	{
		if ($this->auto_render)
		{
			//
		}
		parent::after();
	}

} // END Layout