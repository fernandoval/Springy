<?php
/*
 * urgent_mail.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/mimemessage/urgent_mail.php,v 1.1 2004/07/27 07:53:54 mlemos Exp $
 *
 *
 */

	$urgent_message=new smtp_message_class;
	$urgent_message->localhost="localhost";   /* This computer address */
	$urgent_message->smtp_host="localhost";   /* SMTP server address - irrelevant in this case*/
	$urgent_message->smtp_direct_delivery=1;  /* Deliver directly to the recipients destination SMTP server for urgent delivery */
	$urgent_message->smtp_exclude_address=""; /* In directly deliver mode, the DNS may return the IP of a sub-domain of the default domain for domains that do not exist. If that is your case, set this variable with that sub-domain address. */
	$urgent_message->timeout=15;               /* Adjust the SMTP connection timeout according to what you think it is reasonable to wait before it falls back to normal mail function delivery */
	/*
	 * If the GetMXRR is not functional, you need to use a replacement function.
	 */
	/*
	$_NAMESERVERS=array();
	include("rrcompat.php");
	$urgent_message->smtp_getmxrr="_getmxrr";
	*/
	$urgent_message->smtp_debug=0;            /* Output dialog with SMTP server */
	$urgent_message->smtp_html_debug=1;       /* If smtp_debug is 1, set this to 1 to output debug information in HTML */

Function urgent_mail($to,$subject,$message,$additional_headers="",$additional_parameters="")
{
	global $urgent_message;

	if($urgent_message->Mail($to,$subject,$message,$additional_headers,$additional_parameters))
		return(1);
	return(mail($to,$subject,$message,$additional_headers));
}

?>