<?php
/*
 * test_attachment_message.php
 *
 * @(#) $Header: /opt2/ena/metal/mimemessage/test_attachment_message.php,v 1.4 2012/09/15 09:15:48 mlemos Exp $
 *
 */

	require("email_message.php");


/*
 *  Trying to guess your e-mail address.
 *  It is better that you change this line to your address explicitly.
 *  $from_address="me@mydomain.com";
 *  $from_name="My Name";
 */
	$from_address=getenv("USER")."@".getenv("HOSTNAME");
	$from_name=getenv("USERNAME");

	$reply_name=$from_name;
	$reply_address=$from_address;
	$reply_address=$from_address;
	$error_delivery_name=$from_name;
	$error_delivery_address=$from_address;

/*
 *  Change these lines or else you will be mailing the class author.
 */
	$to_name="Manuel Lemos";
	$to_address="mlemos@acm.org";

	$subject="Testing Manuel Lemos' MIME E-mail composing and sending PHP class for sending messages with attached files";
	$email_message=new email_message_class;
	$email_message->SetEncodedEmailHeader("To",$to_address,$to_name);
	$email_message->SetEncodedEmailHeader("From",$from_address,$from_name);
	$email_message->SetEncodedEmailHeader("Reply-To",$reply_address,$reply_name);
	$email_message->SetHeader("Sender",$from_address);

/*
 *  Set the Return-Path header to define the envelope sender address to which bounced messages are delivered.
 *  If you are using Windows, you need to use the smtp_message_class to set the return-path address.
 */
	if(defined("PHP_OS")
	&& strcmp(substr(PHP_OS,0,3),"WIN"))
		$email_message->SetHeader("Return-Path",$error_delivery_address);

	$email_message->SetEncodedHeader("Subject",$subject);

/*
 *  A message with attached files usually has a text message part
 *  followed by one or more attached file parts.
 */
	$text_message="Hello ".strtok($to_name," ")."\n\nThis message is just to let you know that the MIME E-mail message composing and sending PHP class is working as expected.\n\nYou may find attached to this messages a text file and and image file.\n\nThank you,\n$from_name";
	$email_message->AddQuotedPrintableTextPart($email_message->WrapText($text_message));

	$text_attachment=array(
		"Data"=>"This is just a plain text attachment file named attachment.txt .",
		"Name"=>"attachment.txt",
		"Content-Type"=>"automatic/name",
		"Disposition"=>"attachment"
	);
	$email_message->AddFilePart($text_attachment);

	$image_attachment=array(
		"FileName"=>"http://files.phpclasses.org/graphics/phpclasses/logo-phpclasses.png",
		"Content-Type"=>"automatic/name",
		"Disposition"=>"attachment"
	);
	$email_message->AddFilePart($image_attachment);

/*
 *  The message is now ready to be assembled and sent.
 *  Notice that most of the functions used before this point may fail due to
 *  programming errors in your script. You may safely ignore any errors until
 *  the message is sent to not bloat your scripts with too much error checking.
 */
	$error=$email_message->Send();
	if(strcmp($error,""))
		echo "Error: $error\n";
	else
		echo "Message sent to $to_name\n";
?>