<html>
<head><title>xmlrpc</title></head>
<body>
<?php
	include("xmlrpc.inc");

	$f = new xmlrpcmsg('interopEchoTests.whichToolkit', array());
	$c = new xmlrpc_client("/server.php", "phpxmlrpc.sourceforge.net", 80);
	$c->setDebug(0);
	$r = $c->send($f);
	if(!$r->faultCode())
	{
		$v = php_xmlrpc_decode($r->value());
		print "<pre>";
		print "name: " . htmlspecialchars($v["toolkitName"]) . "\n";
		print "version: " . htmlspecialchars($v["toolkitVersion"]) . "\n";
		print "docs: " . htmlspecialchars($v["toolkitDocsUrl"]) . "\n";
		print "os: " . htmlspecialchars($v["toolkitOperatingSystem"]) . "\n";
		print "</pre>";
	}
	else
	{
		print "Fault: ";
		print "Code: " . htmlspecialchars($r->faultCode()) . " Reason '" . htmlspecialchars($r->faultString()) . "'<BR>";
	}
?>
</body>
</html>
