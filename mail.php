<html>
<head><title>xmlrpc</title></head>
<body>
<?php
// Allow users to see the source of this file even if PHP is not configured for it
if ((isset($HTTP_GET_VARS['highlight']) && $HTTP_GET_VARS['highlight']) ||
	(isset($_GET['highlight']) && $_GET['highlight']))
	{highlight_file(__FILE__); die();}
		
include("xmlrpc.inc");

// Play nice to PHP 5 installations with REGISTER_LONG_ARRAYS off
if (!isset($HTTP_POST_VARS) && isset($_POST))
	$HTTP_POST_VARS = $_POST;

if (isset($HTTP_POST_VARS["server"]) && $HTTP_POST_VARS["server"]) {
	if ($HTTP_POST_VARS["server"]=="Userland") {
		$XP="/RPC2"; $XS="206.204.24.2";
	} else {
		$XP="/xmlrpc/server.php"; $XS="pingu.heddley.com";
	}
	$f=new xmlrpcmsg('mail.send');
	$f->addParam(new xmlrpcval($HTTP_POST_VARS["mailto"]));
	$f->addParam(new xmlrpcval($HTTP_POST_VARS["mailsub"]));
	$f->addParam(new xmlrpcval($HTTP_POST_VARS["mailmsg"]));
	$f->addParam(new xmlrpcval($HTTP_POST_VARS["mailfrom"]));
	$f->addParam(new xmlrpcval($HTTP_POST_VARS["mailcc"]));
	$f->addParam(new xmlrpcval($HTTP_POST_VARS["mailbcc"]));
	$f->addParam(new xmlrpcval("text/plain"));
	
	$c=new xmlrpc_client($XP, $XS, 80);
	$c->setDebug(1);
	$r=$c->send($f);
	//if (!$r) { die("send to ${XS}${XP} port 80 failed: network OK?"); }
	$v=$r->value();
	if (!$r->faultCode()) {
		print "Mail sent OK<BR>\n";
	} else {
		print "<FONT COLOR=\"red\">";
		print "Mail send failed<BR>\n";
		print "Fault: ";
		print "Code: " . htmlspecialchars($r->faultCode()) . 
	  " Reason '" . htmlspecialchars($r->faultString()) . "'<BR>";
		print "</FONT>";
	}
}
?>
<h2>Mail demo</h2>
<P>This form enables you to send mail via an XML-RPC server. For public use
only the "Userland" server will work (see <a href="http://www.xmlrpc.com/discuss/msgReader$598">Dave Winer's message</a>). When you press send this page will reload showing you the XML-RPC message received from the host server, and the internal evaluation done by the PHP implementation.</P>
<P>You can find the source to this page here: <A href="mail.php?highlight=1">mail.php</A><BR>
And the source to a functionally identical mail-by-XML-RPC server in the file server.php included with the library (look for the 'mail_send' method)</P>
<FORM METHOD="POST">
Server <SELECT NAME="server"><OPTION VALUE="Userland">Userland</OPTION>
<OPTION VALUE="UsefulInc">UsefulInc private server</OPTION></SELECT><BR>
Subject <INPUT SIZE=60 NAME="mailsub" VALUE="A message from xmlrpc"><BR>
To <INPUT SIZE=60 NAME="mailto"><BR>
Cc <INPUT SIZE=60 NAME="mailcc"><BR>
Bcc <INPUT SIZE=60 NAME="mailbcc"><BR>
<HR>
From <INPUT SIZE=60 NAME="mailfrom" VALUE=""><BR>
<HR>
Body <TEXTAREA ROWS=7 COLS=60 NAME="mailmsg">Your message here</TEXTAREA><BR>
<INPUT TYPE="Submit" VALUE="Send">
</FORM>
</body>
</html>
