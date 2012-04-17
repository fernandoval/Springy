<?php
/*
 * test_urgent_mail.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/mimemessage/test_urgent_mail.php,v 1.1 2004/07/27 08:20:40 mlemos Exp $
 *
 *
 */

	require("email_message.php");
	require("smtp_message.php");
	require("smtp.php");
	require("urgent_mail.php");

	$urgent_message->smtp_debug=1;
	$urgent_message->smtp_html_debug=0;

	/*
	 *  Change these variables to specify your test sender and recipient addresses
	 */
	$from="mlemos@acm.org";
	$to="mlemos@acm.org";

	$subject="Testing urgent_mail function";
	$message="Hello,\n\nThis message is just to let you know that the urgent_mail() function is working fine as expected.\n\n$from";
	$additional_headers="From: $from";
	$additional_parameters="-f".$from;
	if(urgent_mail($to,$subject,$message,$additional_headers,$additional_parameters))
		echo "Ok.\n";
	else
		echo "Error: ".$message_object->error."\n";

?>