<?php					// -*-c++-*-
	/* $Id: testsuite.php,v 1.1 2005/09/18 03:31:27 wolff_borg Exp $ */

	include('xmlrpc.inc');
	require('phpunit.php');

	// play nice to modern PHP installations with register globals OFF
	// note: php 3 does not have ini_get()
	if(phpversion() >= 4)
	{
		if(!ini_get('register_globals') && function_exists('import_request_variables'))
		{
			@import_request_variables('GP');
		}
	}

	if(!isset($DEBUG))
	{
		$DEBUG = 0;
	}
	if(!isset($LOCALSERVER))
	{
		if(isset($HTTP_HOST))
		{
			$LOCALSERVER = $HTTP_HOST;
		}
		elseif(isset($_SERVER['HTTP_HOST']))
		{
			$LOCALSERVER = $_SERVER['HTTP_HOST'];
		}
		else
		{
			$LOCALSERVER = 'localhost';
		}
	}
	if(!isset($HTTPSSERVER))
	{
		$HTTPSSERVER = 'xmlrpc.usefulinc.com';
	}
	if(!isset($URI))
	{
		// play nice to php 3 and 4-5 in retrieving URL of server.php
		if(isset($REQUEST_URI))
		{
			$URI = str_replace('testsuite.php', 'server.php', $REQUEST_URI);
		}
		elseif(isset($_SERVER['PHP_SELF']))
		{
			$URI = str_replace('testsuite.php', 'server.php', $_SERVER['PHP_SELF']);
		}
		else
		{
			$URI = '/server.php';
		}
	}
	if(!isset($LOCALPATH))
	{
		//$LOCALPATH = '/var/www/xmlrpc';
		$LOCALPATH = dirname(__FILE__);
	}
	$suite = new TestSuite;

	class LocalhostTests extends TestCase
	{
		function LocalhostTests($name)
		{
			$this->TestCase($name);
		}

		function setUp()
		{
			global $DEBUG, $LOCALSERVER, $URI;
			$server = split(':', $LOCALSERVER);
			if(count($server) > 1)
			{
				$this->client=new xmlrpc_client($URI, $server[0], $server[1]);
			}
			else
			{
				$this->client=new xmlrpc_client($URI, $LOCALSERVER);
			}
			if ($DEBUG)
			{
				$this->client->setDebug(1);
			}
		}

		function testString()
		{
			$sendstring="here are 3 \"entities\": < > &" .
				"and here's a dollar sign: \$pretendvarname and a backslash too: " . chr(92) .
				" - isn't that great? \\\"hackery\\\" at it's best " .
				" also don't want to miss out on \$item[0]. ".
				"The real weird stuff follows: CRLF here".chr(13).chr(10).
				"a simple CR here".chr(13).
				"a simple LF here".chr(10).
				"and then LFCR".chr(10).chr(13).
				"last but not least weird names: G¸nter, ElËne";
			$f=new xmlrpcmsg('examples.stringecho', array(
				new xmlrpcval($sendstring, 'string')
			));
			$r=$this->client->send($f);
			$this->assert(!$r->faultCode());
			$v=$r->value();
			$this->assertEquals($sendstring, $v->scalarval());
		}

		function testAddingDoubles()
		{
			// note that rounding errors mean i
			// keep precision to sensible levels here ;-)
			$a=12.13; $b=-23.98;
			$f=new xmlrpcmsg('examples.addtwodouble',array(
				new xmlrpcval($a, 'double'),
				new xmlrpcval($b, 'double')
			));
			$r=$this->client->send($f);
			$this->assert(!$r->faultCode());
			$v=$r->value();
			$this->assertEquals($a+$b,$v->scalarval());
		}

		function testAdding()
		{
			$f=new xmlrpcmsg('examples.addtwo',array(
				new xmlrpcval(12, 'int'),
				new xmlrpcval(-23, 'int')
			));
			$r=$this->client->send($f);
			$this->assert(!$r->faultCode());
			$v=$r->value();
			$this->assertEquals(12-23, $v->scalarval());
		}

		function testInvalidNumber()
		{
			$f=new xmlrpcmsg('examples.addtwo',array(
				new xmlrpcval('fred', 'int'),
				new xmlrpcval("\"; exec('ls')", 'int')
			));
			$r=$this->client->send($f);
			$this->assert(!$r->faultCode());
			$v=$r->value();
			// TODO: a fault condition should be generated here
			// by the server, which we pick up on
			$this->assertEquals(0, $v->scalarval());
		}

		function testBoolean()
		{
			$f=new xmlrpcmsg('examples.invertBooleans', array(
				new xmlrpcval(array(
					new xmlrpcval(true, 'boolean'),
					new xmlrpcval(false, 'boolean'),
					new xmlrpcval(1, 'boolean'),
					new xmlrpcval(0, 'boolean'),
					new xmlrpcval('true', 'boolean'),
					new xmlrpcval('false', 'boolean')
				),
				'array'
			)));
			$answer='010101';
			$r=$this->client->send($f);
			$this->assert(!$r->faultCode());
			$v=$r->value();
			$sz=$v->arraysize();
			$got='';
			for($i=0; $i<$sz; $i++)
			{
				$b=$v->arraymem($i);
				if($b->scalarval())
				{
					$got.='1';
				}
				else
				{
					$got.='0';
				}
			}
			$this->assertEquals($answer, $got);
		}

		function testBase64()
		{
			$sendstring='Mary had a little lamb,
Whose fleece was white as snow,
And everywhere that Mary went
the lamb was sure to go.

Mary had a little lamb
She tied it to a pylon
Ten thousand volts went down its back
And turned it into nylon';
			$f=new xmlrpcmsg('examples.decode64',array(
				new xmlrpcval($sendstring, 'base64')
			));
			$r=$this->client->send($f);
			$v=$r->value();
			$this->assertEquals($sendstring, $v->scalarval());
		}

		function testCountEntities()
		{
			$sendstring = "h'fd>onc>>l>>rw&bpu>q>e<v&gxs<ytjzkami<";
			$f = new xmlrpcmsg('validator1.countTheEntities',array(
				new xmlrpcval($sendstring, 'string')
			));
			$r = $this->client->send($f);
			$v = $r->value();

			$got = '';
			$expected = '37210';
			$expect_array = array('ctLeftAngleBrackets','ctRightAngleBrackets','ctAmpersands','ctApostrophes','ctQuotes');

			while(list(,$val) = each($expect_array))
			{
				$b = $v->structmem($val);
				$got .= $b->me['int'];
			}

			$this->assertEquals($expected, $got);
		}

		function _multicall_msg($method, $params)
		{
			$struct['methodName'] = new xmlrpcval($method, 'string');
			$struct['params'] = new xmlrpcval($params, 'array');
			return new xmlrpcval($struct, 'struct');
		}

		function testServerMulticall()
		{
			// We manually construct a system.multicall() call to ensure
			// that the server supports it.

			// Based on http://xmlrpc-c.sourceforge.net/hacks/test_multicall.py
			if(XMLRPC_EPI_ENABLED == '1')
			{
				$good1 = $this->_multicall_msg(
					'system.methodHelp',
					array(php_xmlrpc_encode('system.listMethods')));
				$bad = $this->_multicall_msg(
					'test.nosuch',
					array(php_xmlrpc_encode(1), php_xmlrpc_encode(2)));
				$recursive = $this->_multicall_msg(
					'system.multicall',
					array(new xmlrpcval(array(), 'array')));
				$good2 = $this->_multicall_msg(
					'system.methodSignature',
					array(php_xmlrpc_encode('system.listMethods')));
				$arg = new xmlrpcval(
					array($good1, $bad, $recursive, $good2),
					'array'
				);
			}
			else
			{
				$good1 = $this->_multicall_msg(
					'system.methodHelp',
					array(xmlrpc_encode('system.listMethods')));
				$bad = $this->_multicall_msg(
					'test.nosuch',
					array(xmlrpc_encode(1), xmlrpc_encode(2)));
				$recursive = $this->_multicall_msg(
					'system.multicall',
					array(new xmlrpcval(array(), 'array')));
				$good2 = $this->_multicall_msg(
					'system.methodSignature',
					array(xmlrpc_encode('system.listMethods')));
				$arg = new xmlrpcval(
					array($good1, $bad, $recursive, $good2),
					'array'
				);
			}
			$f = new xmlrpcmsg('system.multicall', array($arg));
			$r = $this->client->send($f);
			$this->assert($r->faultCode() == 0, "fault from system.multicall");

			$v = $r->value();
			$this->assert($v->arraysize() == 4, "bad number of return values");

			$r1 = $v->arraymem(0);
			$this->assert(
				$r1->kindOf() == 'array' && $r1->arraysize() == 1,
				"did not get array of size 1 from good1");

			$r2 = $v->arraymem(1);
			$this->assert(
				$r2->kindOf() == 'struct',
				"no fault from bad");

			$r3 = $v->arraymem(2);
			$this->assert(
				$r3->kindOf() == 'struct',
				"recursive system.multicall did not fail");

			$r4 = $v->arraymem(3);
			$this->assert(
				$r4->kindOf() == 'array' && $r4->arraysize() == 1,
				"did not get array of size 1 from good2");
		}

		function testClientMulticall()
		{
			// This test will NOT pass if server does not support system.multicall.
			// We should either fix it or build a new test for it...

			if(XMLRPC_EPI_ENABLED == '1')
			{
				$good1 = new xmlrpcmsg('system.methodHelp',
					array(php_xmlrpc_encode('system.listMethods')));
				$bad = new xmlrpcmsg('test.nosuch',
					array(php_xmlrpc_encode(1), php_xmlrpc_encode(2)));
				$recursive = new xmlrpcmsg('system.multicall',
					array(new xmlrpcval(array(), 'array')));
				$good2 = new xmlrpcmsg('system.methodSignature',
					array(php_xmlrpc_encode('system.listMethods'))
				);
			}
			else
			{
				$good1 = new xmlrpcmsg('system.methodHelp',
					array(xmlrpc_encode('system.listMethods')));
				$bad = new xmlrpcmsg('test.nosuch',
					array(xmlrpc_encode(1), xmlrpc_encode(2)));
				$recursive = new xmlrpcmsg('system.multicall',
					array(new xmlrpcval(array(), 'array')));
				$good2 = new xmlrpcmsg('system.methodSignature',
					array(xmlrpc_encode('system.listMethods'))
				);
			}
			$r = $this->client->send(array($good1, $bad, $recursive, $good2));

			$this->assert(count($r) == 4, "wrong number of return values");

			$this->assert($r[0]->faultCode() == 0, "fault from good1");
			$val = $r[0]->value();
			$this->assert(
				$val->kindOf() == 'scalar' && $val->scalartyp() == 'string',
				"good1 did not return string");
			$this->assert($r[1]->faultCode() != 0, "no fault from bad");
			$this->assert($r[2]->faultCode() != 0, "no fault from recursive system.multicall");
			$this->assert($r[3]->faultCode() == 0, "fault from good2");
			$val = $r[3]->value();
			$this->assert($val->kindOf() == 'array', "good2 did not return array");


			// This is the only assert in this test which should fail
			// if the test server does not support system.multicall.
			$this->assert($this->client->no_multicall == false,
				"server does not support system.multicall");
		}

		function testZeroParams()
		{
			$f = new xmlrpcmsg('system.listMethods');
			$r = $this->client->send($f);
			$v = $r->faultCode();
			$this->assertEquals($v, 0);
		}

		function testCodeInjectionServerSide ()
		{
			$f = new xmlrpcmsg('system.MethodHelp');
			$f->payload = "<?xml version=\"1.0\"?><methodCall><methodName>system.MethodHelp</methodName><params><param><value><name>','')); echo('gotcha!'); die(); //</name></value></param></params></methodCall>";
			$r = $this->client->send($f);
			$v = $r->faultCode();
			$this->assertEquals($v, 15);
		}
	}

	class FileCasesTests extends TestCase
	{
		function FileCasesTests($name, $base='')
		{
			global $DEBUG;
			$this->msg = new xmlrpcmsg('dummy');
			if ($DEBUG)
			{
				$this->msg->debug = true;
			}
			if(!$base)
			{
				global $LOCALPATH;
				$base = $LOCALPATH;
			}
			$this->TestCase($name);
			$this->root=$base;
		}

		function testStringBug ()
		{
			$fp=fopen($this->root.'/bug_string.xml', 'r');
			$r=$this->msg->parseResponseFile($fp);
			$v=$r->value();
			fclose($fp);
			$s=$v->structmem('sessionID');
			$this->assertEquals('S300510007I', $s->scalarval());
		}

		function testWhiteSpace ()
		{
			$fp=fopen($this->root.'/bug_whitespace.xml', 'r');
			$r=$this->msg->parseResponseFile($fp);
			$v=$r->value();
			fclose($fp);
			$s=$v->structmem('content');
			$this->assertEquals("hello world. 2 newlines follow\n\n\nand there they were.", $s->scalarval());
		}

		function testWeirdHTTP ()
		{
			$fp=fopen($this->root.'/bug_http.xml', 'r');
			$r=$this->msg->parseResponseFile($fp);
			$v=$r->value();
			fclose($fp);
			$s=$v->structmem('content');
			$this->assertEquals("hello world. 2 newlines follow\n\n\nand there they were.", $s->scalarval());
		}

		function testCodeInjection ()
		{
			$fp=fopen($this->root.'/bug_inject.xml', 'r');
			$r=$this->msg->parseResponseFile($fp);
			$v=$r->value();
			fclose($fp);
			$this->assertEquals(6, count($v->me['struct']));
		}
	}

	class ParsingBugsTests extends TestCase
	{
		function ParsingBugsTests($name)
		{
			$this->TestCase($name);
		}

		function testMinusOneString()
		{
			$v=new xmlrpcval('-1');
			$u=new xmlrpcval('-1', 'string');
			$this->assertEquals($u->scalarval(), $v->scalarval());
		}

		function testUnicodeInErrorString()
		{
			$response = utf8_encode(
'<?xml version="1.0"?>
<!-- $Id -->
<!-- found by G. giunta, covers what happens when lib receives
  UTF8 chars in reponse text and comments -->
<!-- ‡¸Ë&#224;&#252;&#232; -->
<methodResponse>
<fault>
<value>
<struct>
<member>
<name>faultCode</name>
<value><int>888</int></value>
</member>
<member>
<name>faultString</name>
<value><string>‡¸Ë&#224;&#252;&#232;</string></value>
</member>
</struct>
</value>
</fault>
</methodResponse>');
			$m=new xmlrpcmsg('dummy');
			$r=$m->parseResponse($response);
			$v=$r->faultString();
			$this->assertEquals('‡¸Ë‡¸Ë', $v);
		}

		function testValidNumbers ()
		{ 
			$m=new xmlrpcmsg('dummy');
			$fp=
'<?xml version="1.0"?>
<methodResponse>
<params>
<param>
<value>
<struct>
<member> 
<name>integer1</name>
<value><int>01</int></value>
</member>
<member> 
<name>float1</name>
<value><double>01.10</double></value>
</member>
<member> 
<name>integer2</name>
<value><int>+1</int></value>
</member>
<member> 
<name>float2</name>
<value><double>+1.10</double></value>
</member>
<member>
<name>float3</name>
<value><double>-1.10e2</double></value>
</member>
</struct>
</value>
</param>
</params>
</methodResponse>';
			$r=$m->parseResponse($fp);
			$v=$r->value();
			$s=$v->structmem('integer1');
			$t=$v->structmem('float1');
			$u=$v->structmem('integer2');
			$w=$v->structmem('float2');
			$x=$v->structmem('float3');
			$this->assertEquals(1, $s->scalarval());
			$this->assertEquals(1.1, $t->scalarval());
			$this->assertEquals(1, $u->scalarval());
			$this->assertEquals(1.1, $w->scalarval());
			$this->assertEquals(-110.0, $x->scalarval());
		}
	}

	class InvalidHostTests extends TestCase
	{
		function InvalidHostTests($name)
		{
			$this->TestCase($name);
		}

		function setUp()
		{
			global $DEBUG,$LOCALSERVER;
			$this->client=new xmlrpc_client('/NOTEXIST.php', $LOCALSERVER, 80);
			if($DEBUG)
			{
				$this->client->setDebug(1);
			}
		}

		function test404()
		{
			$f=new xmlrpcmsg('examples.echo',array(
				new xmlrpcval('hello', 'string')
			));
			$r=$this->client->send($f);
			$this->assertEquals(5, $r->faultCode());
		}
	}

	class HTTPSConnectionTests extends TestCase
	{
		function HTTPSConnectionTests($name)
		{
			$this->TestCase($name);
		}

		function setUp()
		{
			global $DEBUG,$HTTPSSERVER,$URI;
			$this->client=new xmlrpc_client($URI,$HTTPSSERVER);
			//$this->client->setCertificate('/var/www/xmlrpc/rsakey.pem',
			//			  'test');
			if ($DEBUG || 1)
			{
				$this->client->setDebug(1);
			}
		}

		function testAddingTest()
		{
			$f=new xmlrpcmsg('examples.getStateName',array(
				new xmlrpcval(23, 'int')
			));
			$r=$this->client->send($f, 180, 'https');
			if($r->faultCode())
			{
				// create dummy value so assert fails
				$v=new xmlrpcval('SSL send failed.');
				print '<pre>Fault: ' . $r->faultString() . '</pre>';
			}
			else
			{
				$v=$r->value();
			}
			$this->assertEquals('Michigan', $v->scalarval());
		}
	}

	$suite->addTest(new LocalhostTests('testString'));
	$suite->addTest(new LocalhostTests('testAdding'));
	$suite->addTest(new LocalhostTests('testAddingDoubles'));
	$suite->addTest(new LocalhostTests('testInvalidNumber'));
	$suite->addTest(new LocalhostTests('testBoolean'));
	$suite->addTest(new LocalhostTests('testCountEntities'));
	$suite->addTest(new LocalhostTests('testBase64'));
	$suite->addTest(new LocalhostTests('testServerMulticall'));
	$suite->addTest(new LocalhostTests('testClientMulticall'));
	$suite->addTest(new LocalhostTests('testZeroParams'));
	$suite->addTest(new LocalhostTests('testCodeInjectionServerSide'));

	$suite->addTest(new InvalidHostTests('test404'));

/*	$suite->addTest(new FileCasesTests('testStringBug'));
	$suite->addTest(new FileCasesTests('testWhiteSpace'));
	$suite->addTest(new FileCasesTests('testWeirdHTTP'));
	$suite->addTest(new FileCasesTests('testCodeInjection'));*/

	$suite->addTest(new HTTPSConnectionTests('testAddingTest'));

	$suite->addTest(new ParsingBugsTests('testMinusOneString'));
	$suite->addTest(new ParsingBugsTests('testUnicodeInErrorString'));
	$suite->addTest(new ParsingBugsTests('testValidNumbers'));
	$title = 'XML-RPC Unit Tests';
?>
<html>
<head><title><?php echo $title; ?></title></head>
<body>
<h1><?php echo $title; ?></h1>
<h3>Using lib version: <?php echo $xmlrpcVersion; ?></h3>
<h3>Running tests against servers: http://<?php echo htmlspecialchars($LOCALSERVER.$URI); ?> and https://<?php echo htmlspecialchars($HTTPSSERVER.$URI); ?></h3>
<p>Note, tests beginning with 'f_' <i>should</i> fail.</p>
<p>
<?php
	if(isset($only))
	{
		$suite = new TestSuite($only);
	}

	$testRunner = new TestRunner;
	// do some basic timing measurement
	list($micro, $sec) = explode(' ', microtime());
	$start_time = $sec + $micro;

	$testRunner->run($suite);

	list($micro, $sec) = explode(' ', microtime());
	$end_time = $sec + $micro;
	printf("<h3>Time spent: %.2f secs</h3>\n", $end_time - $start_time);
?>
</body>
</html>
