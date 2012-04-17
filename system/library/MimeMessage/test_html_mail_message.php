<?php
/*
 * test_html_mail_message.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/mimemessage/test_html_mail_message.php,v 1.14 2008/04/07 22:19:08 mlemos Exp $
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

/*
 *  An HTML message that requires any dependent files to be sent,
 *  like image files, style sheet files, HTML frame files, etc..,
 *  needs to be composed as a multipart/related message part.
 *  Different parts need to be created before they can be added
 *  later to the message.
 *
 *  Parts can be created from files that can be opened and read.
 *  The data content type needs to be specified. The can try to guess
 *  the content type automatically from the file name.
 */
	$image=array(
		"FileName"=>"http://www.phpclasses.org/graphics/logo.gif",
		"Content-Type"=>"automatic/name",
		"Disposition"=>"inline",
/*
 *  You can set the Cache option if you are going to send the same message
 *  to multiple users but this file part does not change.
 *
		"Cache"=>1
 */
	);
	$email_message->CreateFilePart($image,$image_part);

/*
 *  Parts that need to be referenced from other parts,
 *  like images that have to be hyperlinked from the HTML,
 *  are referenced with a special Content-ID string that
 *  the class creates when needed.
 */
	$image_content_id=$email_message->GetPartContentID($image_part);

/*
 *  Many related file parts may be embedded in the message.
 */
	$image=array(
		"FileName"=>"http://www.phpclasses.org/graphics/background.gif",
		"Content-Type"=>"automatic/name",
		"Disposition"=>"inline",
/*
 *  You can set the Cache option if you are going to send the same message
 *  to multiple users but this file part does not change.
 *
		"Cache"=>1
 */
	);
	$email_message->CreateFilePart($image,$background_image_part);

/*
 *  Related file parts may also be embedded in the actual HTML code in the
 *  form of URL like those referenced by the SRC attribute of IMG tags.
 *  This example is commented out because not all mail programs support
 *  this method of embedding images in HTML messages.
 *
 *  $image=array(
 *    "FileName"=>"http://www.phpclasses.org/graphics/elephpant_logo.gif",
 *    "Content-Type"=>"automatic/name",
 *  );
 *  $image_data_url=$email_message->GetDataURL($image);
 */

/*
 *  Use different identifiers to reference different related file parts.
 *  Some e-mail programs do not support setting the background image in the
 *  body tag or style. A workaround consists on using a table with 100%
 *  with the background attribute set to the image URL.
 */
		$background_image_content_id="cid:".$email_message->GetPartContentID($background_image_part);

/*
 *  The URL of referenced parts in HTML starts with cid:
 *  followed by the Contentp-ID string. Notice the image link below.
 */
	$html_message="<html>
<head>
<title>$subject</title>
<style type=\"text/css\"><!--
body { color: black ; font-family: arial, helvetica, sans-serif ; background-color: #A3C5CC }
A:link, A:visited, A:active { text-decoration: underline }
--></style>
</head>
<body>
<table background=\"$background_image_content_id\" width=\"100%\">
<tr>
<td>
<center><h1>$subject</h1></center>
<hr>
<P>Hello ".strtok($to_name," ").",<br><br>
This message is just to let you know that the <a href=\"http://www.phpclasses.org/mimemessage\">MIME E-mail message composing and sending PHP class</a> is working as expected.<br><br>
<center><h2>Here is an image embedded in a message as a separate part:</h2></center>
<center><img src=\"cid:".$image_content_id."\"></center>".
/*
 * This example of embedding images in HTML messages is commented out
 * because not all mail programs support this method.
 *
 * <center><h2>Here is an image embedded directly in the HTML:</h2></center>
 * <center><img src=\"".$image_data_url."\"></center>
 */
"Thank you,<br>
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
	$email_message->CreateAlternativeMultipart($alternative_parts,$alternative_part);

/*
 *  All related parts are gathered in a single multipart/related part.
 */
	$related_parts=array(
		$alternative_part,
		$image_part,
		$background_image_part
	);
	$email_message->AddRelatedMultipart($related_parts);

/*
 *  One or more additional parts may be added as attachments.
 *  In this case a file part is added from data provided directly from this script.
 */
	$attachment=array(
		"Data"=>"This is just a plain text attachment file named attachment.txt .",
		"Name"=>"attachment.txt",
		"Content-Type"=>"automatic/name",
		"Disposition"=>"attachment",
/*
 *  You can set the Cache option if you are going to send the same message
 *  to multiple users but this file part does not change.
 *
		"Cache"=>1
 */
	);
	$email_message->AddFilePart($attachment);

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
	var_dump($email_message->parts);
?>