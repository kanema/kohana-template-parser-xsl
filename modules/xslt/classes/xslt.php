<?php defined('SYSPATH') OR die('No direct access allowed.');

/**
 * Acts as an object wrapper for HTML pages with embedded XSL, called "views".
 * Variables can be assigned with the view object and referenced locally within
 * the view.
 *
 * @package    Kohana
 * @category   Base
 * @author     Eduardo Pacheco
 */
class Xslt extends Controller
{
	// Array of global variables
	protected static $_global_data = Array();
	
	// dom variables
	protected $_dom, $xml;

	/**
	 * Returns a new Xslt object. If you do not define the "file" parameter,
	 * you must call [Xslt::set_filename].
	 *
	 *     $xsl = Xslt::factory($file);
	 *
	 * @param   string  xsl filename
	 * @param   array   array of values
	 * @return  Xslt
	 */
	public static function factory($file = NULL, array $data = NULL)
	{
		return new Xslt($file, $data);
	}

	/**
	 * Captures the output that is generated when a view is included.
	 *
	 *     $output = View::capture($file);
	 *
	 * @param   string  filename
	 * @return  string
	 */
	protected static function capture($kohana_view_filename)
	{
		// Capture the view output
		$result = "";
		
		try
		{
			// Load the view within the current scope
			$result = file_get_contents( $kohana_view_filename );
		}
		catch (Exception $e)
		{
			// Re-throw the exception
			throw new Kohana_Exception('Xml view not found: :file',
				array(':file' => $kohana_view_filename));
		}

		// Get the captured output
		return $result;
	}

	/**
	 * Sets a global variable, similar to [Xslt::set], except that the
	 * variable will be accessible to all xsls.
	 *
	 *     Xslt::set_global($name, $value);
	 *
	 * @param   string  variable name or an array of variables
	 * @param   mixed   value
	 * @return  void
	 */
	public static function set_global($key, $value = NULL)
	{
		if (is_array($key))
		{
			foreach ($key as $key2 => $value)
			{
				Xslt::$_global_data[$key2] = $value;
			}
		}
		else
		{
			Xslt::$_global_data[$key] = $value;
		}
	}

	/**
	 * Assigns a global variable by reference, similar to [Xslt::bind], except
	 * that the variable will be accessible to all xsls.
	 *
	 *     Xslt::bind_global($key, $value);
	 *
	 * @param   string  variable name
	 * @param   mixed   referenced variable
	 * @return  void
	 */
	public static function bind_global($key, & $value)
	{
		Xslt::$_global_data[$key] =& $value;
	}

	// Xslt filename
	protected $_file;

	// Array of local variables
	protected $_data = Array();

	/**
	 * Sets the initial xsl filename and local data. Xslts should almost
	 * always only be created using [Xslt::factory].
	 *
	 *     $xsl = new Xslt($file);
	 *
	 * @param   string  xsl filename
	 * @param   array   array of values
	 * @return  void
	 * @uses    Xslt::set_filename
	 */
	public function __construct($file = NULL, array $data = NULL)
	{
		if ($file !== NULL)
		{
			$this->set_filename($file);
		}

		if ( $data !== NULL)
		{
			// Add the values to the current data
			$this->_data = $data + $this->_data;
		}

		// Kohana vars
		$this->set( "kohana", 
			Array
			(
				"charset" => Kohana::$charset,
				"protocol" => (isset($_SERVER['HTTPS'])) ? 'https' : 'http',
				"domain" => $_SERVER['HTTP_HOST'],
				"base" => URL::base(),
				"path" => Request::instance()->uri,
				"action" => request::instance()->action,
				"controller" => request::instance()->controller,
			)
		);

	}

	/**
	 * Magic method, searches for the given variable and returns its value.
	 * Local variables will be returned before global variables.
	 *
	 *     $value = $xsl->foo;
	 *
	 * [!!] If the variable has not yet been set, an exception will be thrown.
	 *
	 * @param   string  variable name
	 * @return  mixed
	 * @throws  Kohana_Exception
	 */
	public function & __get($key)
	{
		if (isset($this->_data[$key]))
		{
			return $this->_data[$key];
		}
		elseif (isset(Xslt::$_global_data[$key]))
		{
			return Xslt::$_global_data[$key];
		}
		else
		{
			throw new Kohana_Exception('Xslt variable is not set: :var',
				array(':var' => $key));
		}
	}

	/**
	 * Magic method, calls [Xslt::set] with the same parameters.
	 *
	 *     $xsl->foo = 'something';
	 *
	 * @param   string  variable name
	 * @param   mixed   value
	 * @return  void
	 */
	public function __set($key, $value)
	{
		$this->set($key, $value);
	}

	/**
	 * Magic method, determines if a variable is set.
	 *
	 *     isset($xsl->foo);
	 *
	 * [!!] `NULL` variables are not considered to be set by [isset](http://php.net/isset).
	 *
	 * @param   string  variable name
	 * @return  boolean
	 */
	public function __isset($key)
	{
		return (isset($this->_data[$key]) OR isset(Xslt::$_global_data[$key]));
	}

	/**
	 * Magic method, unsets a given variable.
	 *
	 *     unset($xsl->foo);
	 *
	 * @param   string  variable name
	 * @return  void
	 */
	public function __unset($key)
	{
		unset($this->_data[$key], Xslt::$_global_data[$key]);
	}

	/**
	 * Magic method, returns the output of [Xslt::render].
	 *
	 * @return  string
	 * @uses    Xslt::render
	 */
	public function __toString()
	{
		try
		{
			return $this->render();
		}
		catch (Exception $e)
		{
			// Display the exception message
			Kohana::exception_handler($e);

			return '';
		}
	}

	/**
	 * Sets the xsl filename.
	 *
	 *     $xsl->set_filename($file);
	 *
	 * @param   string  xsl filename
	 * @return  Xslt
	 * @throws  Kohana_Exception
	 */
	public function set_filename($file)
	{
		// Store the file path locally
		$this->_file = Kohana::find_file("views", $file, "xsl", FALSE);

		if ( empty( $this->_file ) )
		{
			throw new Kohana_Exception('The requested view :file could not be found', array(
				':file' => $file,
			));
		}

		return $this;
	}

	/**
	 * Assigns a variable by name. Assigned values will be available as a
	 * variable within the xsl file:
	 *
	 *     // This value can be accessed as $foo within the xsl
	 *     $xsl->set('foo', 'my value');
	 *
	 * You can also use an array to set several values at once:
	 *
	 *     // Create the values $food and $beverage in the xsl
	 *     $xsl->set(array('food' => 'bread', 'beverage' => 'water'));
	 *
	 * @param   string   variable name or an array of variables
	 * @param   mixed    value
	 * @return  $this
	 */
	public function set($key, $value = NULL)
	{
		if (is_array($key))
		{
			foreach ($key as $name => $value)
			{
				$this->_data[$name] = $value;
			}
		}
		else
		{
			// var_dump( $value );
			$this->_data[$key] = ( is_string($value) ) ? htmlentities($value) : $value;
		}

		return $this;
	}

	/**
	 * Assigns a value by reference. The benefit of binding is that values can
	 * be altered without re-setting them. It is also possible to bind variables
	 * before they have values. Assigned values will be available as a
	 * variable within the xsl file:
	 *
	 *     // This reference can be accessed as $ref within the xsl
	 *     $xsl->bind('ref', $bar);
	 *
	 * @param   string   variable name
	 * @param   mixed    referenced variable
	 * @return  $this
	 */
	public function bind($key, & $value)
	{
		$this->_data[$key] =& $value;

		return $this;
	}

	/**
	 * Convert arry to xsml
	 *
	 *     $this->array2xml( $array );
	 *
	 * @param   array	array input array
	 * @param   string	root node
	 * @param   bool	start (internal)
	 * @return  string
	 */
	function array2xml($array, $root = "", $begin = TRUE)
	{
		$xml = "";
		if ( $begin )
		{
			$xml = "<?xml version=\"1.0\" encoding=\"utf-8\"?>\n<root>";
		};
		foreach ($array as $key => $value)
		{
			if ( is_string( $value ) )
			{
				$value = htmlentities( $value );
			}
			if ( is_array($value) )
			{
				if ( $root === "" )
				{
					if ( is_numeric ( $key ) )
					{
						$xml .= "<$root>". $this->array2xml($value, $key, FALSE) . "</$root>";
					}
					else
					{
						$xml .= "<$key>" . $this->array2xml($value, $key, FALSE) . "</$key>";
					};
				}
				else
				{
					if ( $root === $key )
					{
						$xml .= $this->array2xml($value, $key, FALSE);
					}
					else
					{
						if ( is_numeric ( $key ) )
						{
							$xml .= "<$root>". $this->array2xml($value, $key, FALSE) . "</$root>";
						}
						else
						{
							$xml .= $this->array2xml($value, $key, FALSE);
						};
					};
				};
			}
			else
			{
				if ( is_numeric ( $key ) )
				{
					$xml .= "<$root>$value</$root>";
				}
				else
				{
					$xml .= "<$key>$value</$key>";
				};
			}
		};
		if ( $begin )
		{
			$xml .= "</root>";
		};
		return $xml;
	}
	
	/**
	 * Get the xsl includes
	 *
	 *     $this->get_includes( $xsl );
	 *
	 * @param   string  xsl contents
	 * @return  string
	 */
	public function get_includes( $Xsl )
	{
		$pattern = "/<xsl:include(.*)href=\"(.*)\"(.*)>/i";
		
		preg_match_all( $pattern, $Xsl, $arrIncludes );
		
		if ( ! empty( $arrIncludes[2] ) )
		{
			foreach( $arrIncludes[2] as $key => $value )
			{
				// If is a var
				preg_match( "/^{(.*)}/i", $value, $match );
				if ( ! empty($match) )
				{
					$value_path = $match[0];
					$value = "['" . preg_replace("/\//i", "']['", $match[1]) . "']";

					// TODO Arrumar uma maneira melhor de fazer isso
					eval( '$value = $this->_data'. $value . ';' );
					
				};
				
				$value_path = str_replace("/", "\/", $value);
				
				if ( ! preg_match( "/^http/i", $value ) )
				{
					$file = Kohana::find_file("views", $value_path, "xsl", FALSE);
					
					if ( empty( $file ) )
					{
						throw new Kohana_Exception('The requested xsl :file could not be found', array(
							':file' => $value,
						));
					};
					
					if ( ! empty($match) )
					{
						$value_path = $match[0];
					}
					
					$value = $file;
				};
				
				$Xsl = preg_replace(
					"/<xsl:include(.*)href=\"". $value_path ."\"(.*)>/i",
					file_get_contents( $value ),
					$Xsl
				);
			};
			
			preg_match_all( $pattern, $Xsl, $arrIncludes );
			
			if ( count( $arrIncludes[2] ) )
			{
				$Xsl = $this->get_includes( $Xsl );
			};
		};
		
		return $Xsl;
	}

	/**
	 * Renders the xsl object to a string. Global and local data are merged
	 * and extracted to create local variables within the xsl file.
	 *
	 *     $output = $xsl->render();
	 *
	 * [!!] Global variables with the same key name as local variables will be
	 * overwritten by the local variable.
	 *
	 * @param    string  xsl filename
	 * @return   string
	 * @throws   Kohana_Exception
	 */
	public function render( $file = NULL )
	{
		if ( $file !== NULL )
		{
			$this->set_filename($file);
		}

		if ( empty( $this->_file ) )
		{
			throw new Kohana_Exception('You must set the file to use within your xsl before rendering');
		}
		
		if ( Xslt::$_global_data )
		{
			// Import the global xsl variables to local namespace and maintain references
			$this->_data = array_merge( Xslt::$_global_data, $this->_data );
		}

		// Create the XML DOM
		$this->_dom = new DomDocument('1.0', 'UTF-8');
		$this->_dom->preserveWhiteSpace = FALSE;
		$this->_dom->formatOutput = TRUE;

		// echo $this->array2xml( $this->_data );
		$this->_dom->loadXML( $this->array2xml( $this->_data ) );
		
		// Link the xml and xsl
		$this->_dom->insertBefore(
			$this->_dom->createProcessingInstruction(
				'xml-stylesheet',
				'type="text/xsl" href="' . $this->_file . '"'
			)
		);
		
		// Load the xsl
		$xslt = new DOMDocument;
		
		$doc = Array(
			"content" => Xslt::capture( $this->_file ),
			"pattern" => "/<!DOCTYPE (.*) PUBLIC \"(.*)\" \"(.*)\">/i",
			"match" => Array(),
			"method" => "xml",
			"public" => "",
			"system" => ""
		);
		preg_match($doc["pattern"] , $doc["content"], $match );
		
		if ( ! empty( $match ) )
		{
			$doc["content"] = preg_replace($doc["pattern"], "", $doc["content"]);
			$doc["match"] = $match;
			$doc["method"] = $doc["match"][1];
			$doc["public"] = "doctype-public=\"". $doc["match"][2] . "\"";
			$doc["system"] = "doctype-system=\"". $doc["match"][3] . "\"";
		};
		
		$doc["content"] = $this->get_includes( $doc["content"] );

		$xslt->loadXML(
			"<?xml version=\"1.0\" encoding=\"UTF-8\"?>
			<xsl:stylesheet version=\"1.0\" xmlns:xsl=\"http://www.w3.org/1999/XSL/Transform\">
			<xsl:output
				method=\"xml\"
				encoding=\"". Kohana::$charset ."\"
				omit-xml-declaration=\"no\"
				". $doc["public"] ."
				". $doc["system"] ."
				indent=\"no\"
				/>

			<xsl:template match=\"/root\">".
				$doc["content"] .
			"</xsl:template></xsl:stylesheet>"
		);

		// Process XSLT
		$proc = new xsltprocessor();
		$proc->importStyleSheet( $xslt );

		// Return HTML
		$return_html = $proc->transformToDoc( $this->_dom )->saveXML();
		$return_html= html_entity_decode( $return_html );
		return str_replace("<?xml version=\"1.0\" encoding=\"utf-8\"?>\n", "", $return_html );
		
	}

}
