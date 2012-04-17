<?
/*
 * test_multibyte_message.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/PHPlibrary/mimemessage/test_multibyte_message.php,v 1.1 2002/11/14 05:48:01 mlemos Exp $
 *
 */

	require("email_message.php");

	/*  <culturalmoment>
	 *  Did you know that the Japanese word for "thank you" came from the Portuguese "Obrigado"? ;-)
	 *  </culturalmoment>
	 */
	$charset="ISO-2022-JP";
	$multibyte_characters="\x1B\x24BM-\x24jFq\x24\x26\x1B\x28B";

	if(!IsSet($from_name))
		$from_name=getenv("USERNAME");
	if(!IsSet($from_address))
		$from_address=getenv("USER")."@".getenv("HOSTNAME");

	if(!IsSet($to_name))
		$to_name="Manuel Lemos";
	if(!IsSet($to_address))
		$to_address="mlemos@acm.org";

	$title="Testing Manuel Lemos' MIME Email composition PHP class with some multibyte characters: $multibyte_characters";
	$message="Hello $to_name,\n\nThis message is just to let you know that the e-mail sending class is working as expected.\n\nHere are some multibyte characters $multibyte_characters in the message body to let you see if they are sent properly encoded.\n\nThank you.\n$from_name\n";

	if(!IsSet($multibyte_subject))
		$multibyte_subject=$title;

	if(!IsSet($multibyte_body))
		$multibyte_body=$message;

	Header("Content-Type: text/html; charset=\"$charset\"");
	echo "<html>\n<head>\n<meta HTTP-EQUIV=\"Content-Type\" CONTENT=\"text/html; charset=$charset\" /><title>$title</title></head><body>";
	echo "<h1><center>".$title."</center></h1>\n<hr />\n";

	if(IsSet($send))
	{
		$reply_name=$from_name;
		$reply_address=$from_address;
		$reply_address=$from_address;
		$error_delivery_name=$from_name;
		$error_delivery_address=$from_address;

		$email_message=new email_message_class;
		$email_message->default_charset=$charset;
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

		$email_message->SetEncodedHeader("Subject",$multibyte_subject);
		$email_message->AddPlainTextPart($multibyte_body);
		$error=$email_message->Send();
		if(strlen($error)==0)
		{
			echo "<center><h2>Message sent.</h2></center>\n";
			echo "<center><table border=\"1\">\n";
			echo "<tr>\n<th>From:</th>\n<td>$from_name &lt;$from_address&gt</td>\n</tr>\n";
			echo "<tr>\n<th>To:</th>\n<td>$to_name &lt;$to_address&gt</td>\n</tr>\n";
 			echo "<tr>\n<th>Subject:</th>\n<td>$multibyte_subject</td>\n</tr>\n";
			echo "<tr>\n<th valign=\"top\">Body:</th>\n<td>".nl2br($multibyte_body)."</td>\n</tr>\n";
			echo "</table></center>\n";
		}
		else
			echo "<center><h2>Error: ".HtmlEntities($error)."</h2></center>\n";
	}
	else
	{
		echo "<form method=\"POST\" action=\"$PHP_SELF\">\n";
		echo "<center><table>\n";
		echo "<tr>\n<th>From:</th>\n<td><input type=\"text\" name=\"from_name\" value=\"$from_name\"> &lt;<input type=\"text\" name=\"from_address\" value=\"$from_address\">&gt</td>\n</tr>\n";
		echo "<tr>\n<th>To:</th>\n<td><input type=\"text\" name=\"to_name\" value=\"$to_name\"> &lt;<input type=\"text\" name=\"to_address\" value=\"$to_address\">&gt</td>\n</tr>\n";
		echo "<tr>\n<th>Subject:</th>\n<td><input type=\"text\" name=\"multibyte_subject\" value=\"$multibyte_subject\"></td>\n</tr>\n";
		echo "<tr>\n<th valign=\"top\">Body:</th>\n<td><textarea cols=\"75\" rows=\"10\" name=\"multibyte_body\">$multibyte_body</textarea></td>\n</tr>\n";
		echo "<tr><td colspan=\"2\"><center><input type=\"submit\" name=\"send\" value=\"Send message\"></center></td>\n</tr>\n";
		echo "</table></center>\n";
		echo "</form>\n";
	}
	echo "<hr />\n</body>\n</html>\n";
?>