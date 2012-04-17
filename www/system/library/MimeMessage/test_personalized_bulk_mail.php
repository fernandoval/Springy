<?php
/*
 * test_personalized_bulk_mail.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/mimemessage/test_personalized_bulk_mail.php,v 1.6 2005/02/16 04:04:03 mlemos Exp $
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

	/* Define recipient personalization data. Change it before testing. */
	$to=array(
		array(
			"address"=>"peter@gabriel.org",
			"name"=>"Peter Gabriel"
		),
		array(
			"address"=>"paul@simon.net",
			"name"=>"Paul Simon"
		),
		array(
			"address"=>"mary@chain.com",
			"name"=>"Mary Chain"
		)
	);

	$subject="Testing Manuel Lemos' MIME Email composition PHP class for sending personalized bulk mail";

	$email_message=new email_message_class;

	/*
	 *  For faster queueing use qmail...
	 *
	 *  require_once("qmail_message.php");
	 *  $email_message=new qmail_message_class;
	 *
	 *  or sendmail in queue only delivery mode
	 *
	 *  require_once("sendmail_message.php");
	 *  $email_message=new sendmail_message_class;
	 *  $email_message->delivery_mode=SENDMAIL_DELIVERY_QUEUE;
	 *
	 *  Always call the SetBulkMail function to hint the class to optimize
	 *  its behaviour to make deliveries to many users more efficient.
	 */

	$email_message->SetBulkMail(1);

	$email_message->SetEncodedEmailHeader("From",$from_address,$from_name);
	$email_message->SetEncodedEmailHeader("Reply-To",$reply_address,$reply_name);
	/*
	 *  Set the Return-Path header to define the envelope sender address to which bounced messages are delivered.
	 *  If you are using Windows, you need to use the smtp_message_class to set the return-path address.
	 */
	if(defined("PHP_OS")
	&& strcmp(substr(PHP_OS,0,3),"WIN"))
		$email_message->SetHeader("Return-Path",$error_delivery_address);
	$email_message->SetEncodedEmailHeader("Errors-To",$error_delivery_address,$error_delivery_name);
	$email_message->SetEncodedHeader("Subject",$subject);

	/* If you are not going to personalize the message body for each recipient,
	 * set the cache_body flag to 1 to reduce the time that the class will take
	 * to regenerate the message to send to each recipient */
	$email_message->cache_body=0;

	$message="Hello,\n\nThis message is just to let you know that Manuel Lemos' e-mail sending class is working as expected for sending personalized messages.\n\nThank you,\n$from_name";
	/* Create empty parts for the parts that will be personalized for each recipient. */
	$email_message->CreateQuotedPrintableTextPart($message,"",$text_part);

	/* Add the empty part wherever it belongs in the message. */
	$email_message->AddPart($text_part);

	/* Iterate personalization for each recipient. */
	for($recipient=0;$recipient<count($to);$recipient++)
	{

		/* Personalize the recipient address. */
		$to_address=$to[$recipient]["address"];
		$to_name=$to[$recipient]["name"];
		$email_message->SetEncodedEmailHeader("To",$to_address,$to_name);

		/* Do we really need to personalize the message body?
		 * If not, let the class reuse the message body defined for the first recipient above.
		 */
		if(!$email_message->cache_body)
		{
			/* Create a personalized body part. */
			$message="Hello ".strtok($to_name," ").",\n\nThis message is just to let you know that Manuel Lemos' e-mail sending class is working as expected for sending personalized messages.\n\nThank you,\n$from_name";
			$email_message->CreateQuotedPrintableTextPart($email_message->WrapText($message),"",$recipient_text_part);

			/* Make the personalized replace the initially empty part */
			$email_message->ReplacePart($text_part,$recipient_text_part);
		}

		/* Send the message checking for eventually acumulated errors */
		$error=$email_message->Send();
		if(strlen($error))
			break;
	}

	/* When you are done with bulk mailing call the SetBulkMail function
	 * again passing 0 to tell the all deliveries were done.
	 */
	$email_message->SetBulkMail(0);

	if(strlen($error))
		echo "Error: $error\n";

	echo "Done!\n";
?>