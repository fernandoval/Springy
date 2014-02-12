<?php
/*
 * test_simple_html_mail_message.php
 *
 * @(#) $Header: /opt2/ena/metal/mimemessage/test_simple_html_mail_message.php,v 1.1 2005/02/10 03:45:02 mlemos Exp $
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
	$to_address="mlemos@linux.local";

	$subject="Testing Manuel Lemos' MIME E-mail composing and sending PHP class: HTML message";
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

	$html_message="<html>
<head>
<title>$subject</title>
<style type=\"text/css\"><!--
body { color: black ; font-family: arial, helvetica, sans-serif ; background-color: #A3C5CC }
A:link, A:visited, A:active { text-decoration: underline }
--></style>
</head>
<body>
<table width=\"100%\">
<tr>
<td>
<center><h1>$subject</h1></center>
<hr>
<P>Hello ".strtok($to_name," ").",<br><br>
This message is just to let you know that the <a href=\"http://www.phpclasses.org/mimemessage\">MIME E-mail message composing and sending PHP class</a> is working as expected.<br><br>
Thank you,<br>
$from_name</p>
</td>
</tr>
</table>
</body>
</html>";
	$email_message->CreateQuotedPrintableHTMLPart($html_message,"",$html_part);

/*
 *  It is strongly recommended that when you send HTML messages,
 *  also provide an alternative text version of HTML page,
 *  even if it is just to say that the message is in HTML,
 *  because more and more people tend to delete HTML only
 *  messages assuming that HTML messages are spam.
 */
	$text_message="This is an HTML message. Please use an HTML capable mail program to read this message.";
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