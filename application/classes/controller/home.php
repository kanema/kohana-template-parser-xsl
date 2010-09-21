<?php
class Controller_Home extends Controller_Layout
{
	public function action_index()
	{	
		$this->template->content = "home";
		
		$this->template->title = 'Home';
		
		$this->template->media = Array(
			"script" => Array(
				"http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"
			)
		);

		$this->template->items = Array(
			"item" => Array
			(
				Array(
					"name"	=> "Teste 1",
					"value"	=> "10"
				),
				Array(
					"name" => "Teste 2",
					"value"	=> "20"
				),
				Array(
					"name" => "Teste 3",
					"value"	=> "30"
				),
				Array(
					"name" => "Teste 4",
					"value"	=> "40"
				)
			)
		);
	}
}
?>