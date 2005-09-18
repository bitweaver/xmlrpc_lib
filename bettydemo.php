<html>
<head><title>xmlrpc</title></head>
<body>
<?php
	include("xmlrpc.inc");

	// Play nice to PHP 5 installations with REGISTER_LONG_ARRAYS off
	if (!isset($HTTP_POST_VARS) && isset($_POST))
	{
		$HTTP_POST_VARS = $_POST;
	}
  
	if(@isset($HTTP_POST_VARS["stateno"]) && $HTTP_POST_VARS["stateno"]!="")
	{
		$stateno = $HTTP_POST_VARS["stateno"];
		$f = new xmlrpcmsg(
			'examples.getStateName',
			array(new xmlrpcval($stateno, "int"))
		);
		$c = new xmlrpc_client("/RPC2", "betty.userland.com", 80);
		$r = $c->send($f);
		$v = $r->value();
		if (!$r->faultCode())
		{
			echo "State number ". htmlentities($stateno) . " is "
				. htmlentities($v->scalarval()) . "<BR>";
			echo "<HR>I got this value back<BR><PRE>"
				. htmlentities($r->serialize()). "</PRE><HR>\n";
		}
		else
		{
			echo "Fault: ";
			echo "Code: " . htmlentities($r->faultCode())
				. " Reason '" .htmlentities($r->faultString())."'<BR>";
		}
	}
	else
	{
		$stateno = "";
	}
	echo "<FORM METHOD=\"POST\">
<INPUT NAME=\"stateno\" VALUE=\"".htmlentities($stateno)."\"><input type=\"submit\" value=\"go\" name=\"submit\"></FORM><P>
enter a state number to query its name";
?>
</body>
</html>
