<?php defined('SYSPATH') or die('No direct script access.');

class Controller_Welcome extends Controller
{

	public function action_index()
	{

		$xsl = Xslt::factory("welcome");
		
		$xsl->media = Array(
			"script" => Array(
				"http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js"
			)
		);
		
		$xsl->snippets = Array(
			"header" => Xslt::factory("snippets/header")->render(),
			"footer" => Xslt::factory("snippets/footer")->render()
		);
		
		$xsl->title = "Teste";
		$xsl->items = Array
		(
			"item" => Array(
				Array(
					"name"=>"test 1",
					"value"=>"value 1"
				),
				Array(
					"name"=>"test 2",
					"value"=>"value 2"
				),
				Array(
					"name"=>"test 3",
					"value"=>"value 3"
				)
			)
		);

		$this->request->response = $xsl->render();

	}


} // End Welcome