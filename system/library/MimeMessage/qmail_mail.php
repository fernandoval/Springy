<?php
/*
 * qmail_mail.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/PHPlibrary/mimemessage/qmail_mail.php,v 1.1 2002/09/30 04:37:26 mlemos Exp $
 *
 *
 */

	require_once("email_message.php");
	require_once("qmail_message.php");

	$message_object=new qmail_message_class;

Function qmail_mail($to,$subject,$message,$additional_headers="",$additional_parameters="")
{
	global $message_object;

	return($message_object->Mail($to,$subject,$message,$additional_headers,$additional_parameters));
}

?>