<html>
<head><title>xmlrpc</title></head>
<body>
<?php
	include("xmlrpc.inc");

	// Play nice to PHP 5 installations with REGISTER_LONG_ARRAYS off
	if(!isset($HTTP_POST_VARS) && isset($_POST))
	{
		$HTTP_POST_VARS = $_POST;
	}

	if(isset($HTTP_POST_VARS["stateno"]) && $HTTP_POST_VARS["stateno"]!="")
	{
		$stateno=$HTTP_POST_VARS["stateno"];
		$f=new xmlrpcmsg('examples.getStateName',
			array(new xmlrpcval($stateno, "int"))
		);
		print "<PRE>Sending the following request:<BR>" . htmlentities($f->serialize()) . "</PRE>\n";
		$c=new xmlrpc_client("/server.php", "phpxmlrpc.sourceforge.net", 80);
		$c->setDebug(1);
		$r=$c->send($f);
		$v=$r->value();
		if(!$r->faultCode())
		{
			print "State number ". htmlspecialchars($stateno) . " is "
				. htmlspecialchars($v->scalarval()) . "<BR>";
			// print "<HR>I got this value back<BR><PRE>" .
			//  htmlentities($r->serialize()). "</PRE><HR>\n";
		}
		else
		{
			print "Fault: ";
			print "Code: " . htmlspecialchars($r->faultCode())
				. " Reason '" . htmlspecialchars($r->faultString()) . "'<BR>";
		}
	}
	else
	{
		$stateno = "";
	}

	print "<FORM ACTION=\"client.php\" METHOD=\"POST\">
<INPUT NAME=\"stateno\" VALUE=\"" . htmlspecialchars($stateno) . "\"><input type=\"submit\" value=\"go\" name=\"submit\"></FORM><P>
enter a state number to query its name";

?>
</body>
</html>
