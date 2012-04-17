<?php
/*
 * test_pickup_message.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/mimemessage/test_pickup_message.php,v 1.2 2004/09/22 20:13:26 mlemos Exp $
 *
 */

	require("email_message.php");
	require("pickup_message.php");

	$from_name=getenv("USERNAME");
	$from_address="me@address.com";
	$reply_name=$from_name;
	$reply_address=$from_address;
	$reply_address=$from_address;
	$error_delivery_name=$from_name;
	$error_delivery_address=$from_address;
	$to_name="Manuel Lemos";
	$to_address="mlemos@acm.org";
	$subject="Testing Manuel Lemos' Email Windows pickup sending PHP class";
	$message="Hello ".strtok($to_name," ").",\n\nThis message is just to let you know that your Windows pickup e-mail sending class is working as expected.\n\nThank you,\n$from_name";
	$email_message=new pickup_message_class;

	/*
	 *  Set this to your mail server root directory.
	 *  Set it to an empty string to let the class determine the directory
	 *  path automatically checking the registry.
	 */
	$email_message->mailroot_directory="";

	$email_message->SetEncodedEmailHeader("To",$to_address,$to_name);
	$email_message->SetEncodedEmailHeader("From",$from_address,$from_name);
	$email_message->SetEncodedEmailHeader("Reply-To",$reply_address,$reply_name);
	$email_message->SetHeader("Return-Path",$error_delivery_address);
	$email_message->SetEncodedHeader("Subject",$subject);
	$email_message->AddQuotedPrintableTextPart($email_message->WrapText($message));
	$error=$email_message->Send();
	if(strcmp($error,""))
		echo "Error: $error\n";
?>