<?php
/*
 * test_smarty_personalized_mailing.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/mimemessage/test_smarty_personalized_mailing.php,v 1.5 2005/02/16 04:04:03 mlemos Exp $
 *
 */

	require("email_message.php");

/*
 * Include Smarty template engine class. Make sure the class file is in
 * your include path.
 *
 */
	require('Smarty.class.php');

/*
 * Assign the $from_name and $from_address to your real sender name and
 * address or else the message may not be accepted.
 *
 */
	$from_name=getenv("USERNAME");
	$from_address=getenv("USER")."@".getenv("HOSTNAME");
	$reply_name=$from_name;
	$reply_address=$from_address;
	$reply_address=$from_address;
	$error_delivery_name=$from_name;
	$error_delivery_address=$from_address;

	/* Define recipient personalization data. Change this before testing. */
	$to=array(
		array(
			"address"=>"peter@gabriel.org",
			"name"=>"Peter Gabriel",
			"balance"=>1234
		),
		array(
			"address"=>"paul@simon.net",
			"name"=>"Paul Simon",
			"balance"=>567890
		),
		array(
			"address"=>"mary@chain.com",
			"name"=>"Mary Chain",
			"balance"=>-1234
		)
	);

	$subject="Testing Manuel Lemos' MIME Email composition PHP class for sending personalized bulk mail using HTML and text template with the Smarty engine";

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
	$email_message->SetEncodedHeader("Subject",$subject);

	/*
	 *  If you are not going to personalize the message body for each recipient,
	 *  set the cache_body flag to 1 to reduce the time that the class will take
	 *  to regenerate the message to send to each recipient
	 */
	$email_message->cache_body=0;

	/*
	 *  Lets use two distinct Smarty objects for composing the HTML and text
	 *  parts and avoid the need to re-assign constant values when switching
	 *  contexts.
	 */
	$html_smarty=new Smarty;
	$text_smarty=new Smarty;
	if(!$html_smarty->template_exists($template="mailing.html.tpl")
	|| !$text_smarty->template_exists($template="mailing.txt.tpl"))
	{
		echo "Please copy the template file templates/".$template." to your Smarty templates directory.\n";
		exit;
	}


	/*  Create empty parts for the parts that will be personalized for each recipient.
	 *  HTML message values should be escaped with HTMLEntities().
	 */
	$html_smarty->assign("subject",HtmlEntities($subject));
	$html_smarty->assign("fromname",HtmlEntities($from_name));
	$html_smarty->assign("firstname","");
	$html_smarty->assign("balance","0");
	$html_smarty->assign("email","?");
	$html_message=$html_smarty->fetch("mailing.html.tpl");

	$email_message->CreateQuotedPrintableHTMLPart($html_message,"",$html_part);

/*
 *  It is strongly recommended that when you send HTML messages,
 *  also provide an alternative text version of HTML page,
 *  even if it is just to say that the message is in HTML,
 *  because more and more people tend to delete HTML only
 *  messages assuming that HTML only messages are spam.
 */
	$text_smarty->assign("subject",$subject);
	$text_smarty->assign("fromname",$from_name);
	$text_smarty->assign("firstname","");
	$text_smarty->assign("balance","0");
	$text_smarty->assign("email","?");
	$text_message=$text_smarty->fetch("mailing.txt.tpl");
	$email_message->CreateQuotedPrintableTextPart($email_message->WrapText($text_message),"",$text_part);

/*
 *  Multiple alternative parts are gathered in multipart/alternative parts.
 *  It is important that the fanciest part, in this case the HTML part,
 *  is specified as the last part because that is the way that HTML capable
 *  mail programs will show that part and not the text version part.
 */
	$alternative_parts=array(
		$text_part,
		$html_part
	);
	$email_message->AddAlternativeMultipart($alternative_parts);

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
			/*
			 *  Create a personalized body parts, either HTML and text
			 *  alternative parts.
			 */

			$first_name=strtok($to_name," ");
			$balance=number_format($to[$recipient]["balance"],2);

			$html_smarty->assign("firstname",HtmlEntities($first_name));
			$html_smarty->assign("balance",HtmlEntities($balance));
			$html_smarty->assign("email",HtmlEntities($to_address));
			$email_message->CreateQuotedPrintableHtmlPart($html_smarty->fetch("mailing.html.tpl"),"",$recipient_html_part);

			/* Make the personalized replace the initially empty HTML part */
			$email_message->ReplacePart($html_part,$recipient_html_part);

			$text_smarty->assign("firstname",$first_name);
			$text_smarty->assign("balance",$balance);
			$text_smarty->assign("email",$to_address);
			$email_message->CreateQuotedPrintableTextPart($email_message->WrapText($text_smarty->fetch("mailing.txt.tpl")),"",$recipient_text_part);

			/* Make the personalized replace the initially empty text part */
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