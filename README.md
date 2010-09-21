Kohana Template Parser XSL
=============

Intro
-------

I got a little deeper into Kohana framework 3. As a result of my early studies created a module for XSL using the same template.
The module enables the use of a template or a common view.

[Download XSL Module]: http://eduardo.pacheco.kanema.com.br/wp-content/uploads/2010/09/xslt.zip

Basically works as follows:
Add the module to xsl module folder.
Modify the `bootstrap.php` file in the folder application by adding the XSL module in this way:
	Kohana::modules(array(
		...
		'xslt'  => MODPATH.'xslt' // Template XSLT
		...
	));


Add a route home to `bootstrap.php`:
	Route::set('default', '((/(/)))')
		->defaults(array(
		'controller' => 'home',
		'action'     => 'index',
	));


Application in the folder `application/class/controller` `layout.php` create a file with this contents:
	Class Controller_Layout extends Controller_Xslt
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
	
And another controller called `home.php` with content
	Class Controller_Home extends Controller_Layout
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
						"name"	=> "Test 1",
						"value"	=> "10"
					),
					Array(
						"name" => "Test 2",
						"value"	=> "20"
					),
					Array(
						"name" => "Test 3",
						"value"	=> "30"
					),
					Array(
						"name" => "Test 4",
						"value"	=> "40"
					)
				)
			);
		}
	}


Now in the folder `application/views` create a file called `layout.xsl`, with the contents:
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset={kohana/charset}" />
			<base href="{kohana/base}" />
			<xsl:for-each select="media/script">
				<script src="{text()}" type="text/javascript"></script>
			</xsl:for-each>
		</head>

		<body>

			<xsl:include href="{content}" />

		</body>

	</html>


And another view with the name `home.xsl`:
	<xsl:if test="items">
		<ul>
			<xsl:for-each select="items/item">
				<li><xsl:value-of select="name" /> - <xsl:value-of select="value" /></li>
			</xsl:for-each>
		</ul>
	</xsl:if>


Result:
	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
		<head>
			<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
			<base href="http://localhost/kohana/" />
			<script src="http://ajax.googleapis.com/ajax/libs/jquery/1.4.2/jquery.min.js" type="text/javascript"></script>
		</head>

		<body>
			<h1>Home</h1>

			<ul>
				<li>Teste 1 - 10</li>
				<li>Teste 2 - 20</li>
				<li>Teste 3 - 30</li>
				<li>Teste 4 - 40</li>
			</ul> 

			Desenvolvido por <a href="http://www.kanema.com.br/" title="Eduardo Pacheco">Eduardo Pacheco</a>
		</body>
	</html>