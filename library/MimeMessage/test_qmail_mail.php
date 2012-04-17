<?php
/*
 * test_qmail_mail.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/PHPlibrary/mimemessage/test_qmail_mail.php,v 1.2 2003/10/05 17:44:03 mlemos Exp $
 *
 *
 */

	require("qmail_mail.php");

	/*
	 *  Change these variables to specify your test sender and recipient addresses
	 */
	$from="mlemos@acm.org";
	$to="mlemos@acm.org";

	$subject="Testing qmail_mail function";
	$message="Hello,\n\nThis message is just to let you know that the qmail_mail() function is working fine as expected.\n\n$from";
	$additional_headers="From: $from";
	$additional_parameters="-f ".$from;
	if(qmail_mail($to,$subject,$message,$additional_headers,$additional_parameters))
		echo "Ok.";
	else
		echo "Error: ".$message_object->error."\n";

?>