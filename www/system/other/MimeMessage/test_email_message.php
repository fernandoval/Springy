<?php
/*
 * test_email_message.php
 *
 * @(#) $Header: /opt2/ena/metal/mimemessage/test_email_message.php,v 1.6 2003/10/05 17:32:56 mlemos Exp $
 *
 */

	require("email_message.php");

	$from_name=getenv("USERNAME");
	$from_address=getenv("USER")."@".getenv("HOSTNAME");
	$reply_name=$from_name;
	$reply_address=$from_address;
	$reply_address=$from_address;
	$error_delivery_name=$from_name;
	$error_delivery_address=$from_address;
	$to_name="Manuel Lemos";
	$to_address="mlemos@acm.org";
	$subject="Testing Manuel Lemos' MIME Email composition PHP class: some non-ASCII characters ม่ฮ๕Ü in a message header";
	$message="Hello ".strtok($to_name," ").",\n\nThis message is just to let you know that your e-mail sending class is working as expected.\n\nHere's some non-ASCII characters ม่ฮ๕Ü in the message body to let you see if they are sent properly encoded.\n\nThank you,\n$from_name";
	$email_message=new email_message_class;
	$email_message->SetEncodedEmailHeader("To",$to_address,$to_name);
	$email_message->SetEncodedEmailHeader("From",$from_address,$from_name);
	$email_message->SetEncodedEmailHeader("Reply-To",$reply_address,$reply_name);
/*
	Set the Return-Path header to define the envelope sender address to which bounced messages are delivered.
	If you are using Windows, you need to use the smtp_message_class to set the return-path address.
*/
	if(defined("PHP_OS")
	&& strcmp(substr(PHP_OS,0,3),"WIN"))
		$email_message->SetHeader("Return-Path",$error_delivery_address);
	$email_message->SetEncodedEmailHeader("Errors-To",$error_delivery_address,$error_delivery_name);
	$email_message->SetEncodedHeader("Subject",$subject);
	$email_message->AddQuotedPrintableTextPart($email_message->WrapText($message));
	$error=$email_message->Send();
	if(strcmp($error,""))
		echo "Error: $error\n";
?>