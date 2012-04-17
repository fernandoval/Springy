<?php
/*
 * test_smtp_message.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/mimemessage/test_smtp_message.php,v 1.15 2011/03/09 07:48:52 mlemos Exp $
 *
 */

	require("email_message.php");
	require("smtp_message.php");
	require("smtp.php");
	/* Uncomment when using SASL authentication mechanisms */
	/*
	require("sasl.php");
	*/

	$from_name=getenv("USERNAME");
	$from_address="";                                              $sender_line=__LINE__;
	$reply_name=$from_name;
	$reply_address=$from_address;
	$reply_address=$from_address;
	$error_delivery_name=$from_name;
	$error_delivery_address=$from_address;
	$to_name="Manuel Lemos";
	$to_address="";                                                $recipient_line=__LINE__;
	$subject="Testing Manuel Lemos' Email SMTP sending PHP class";
	$message="Hello ".strtok($to_name," ").",\n\nThis message is just to let you know that your SMTP e-mail sending class is working as expected.\n\nThank you,\n$from_name";

	if(strlen($from_address)==0)
		die("Please set the messages sender address in line ".$sender_line." of the script ".basename(__FILE__)."\n");
	if(strlen($to_address)==0)
		die("Please set the messages recipient address in line ".$recipient_line." of the script ".basename(__FILE__)."\n");

	$email_message=new smtp_message_class;

	/* This computer address */
	$email_message->localhost="localhost";

	/* SMTP server address, probably your ISP address,
	 * or smtp.gmail.com for Gmail
	 * or smtp.live.com for Hotmail */
	$email_message->smtp_host="localhost";

	/* SMTP server port, usually 25 but can be 465 for Gmail */
	$email_message->smtp_port=25;

	/* Use SSL to connect to the SMTP server. Gmail requires SSL */
	$email_message->smtp_ssl=0;

	/* Use TLS after connecting to the SMTP server. Hotmail requires TLS */
	$email_message->smtp_start_tls=0;

	/* Change this variable if you need to connect to SMTP server via an HTTP proxy */
	$email_message->smtp_http_proxy_host_name='';
	/* Change this variable if you need to connect to SMTP server via an HTTP proxy */
	$email_message->smtp_http_proxy_host_port=3128;

	/* Change this variable if you need to connect to SMTP server via an SOCKS server */
	$email_message->smtp_socks_host_name = '';
	/* Change this variable if you need to connect to SMTP server via an SOCKS server */
	$email_message->smtp_socks_host_port = 1080;
	/* Change this variable if you need to connect to SMTP server via an SOCKS server */
	$email_message->smtp_socks_version = '5';


	/* Deliver directly to the recipients destination SMTP server */
	$email_message->smtp_direct_delivery=0;

	/* In directly deliver mode, the DNS may return the IP of a sub-domain of
	 * the default domain for domains that do not exist. If that is your
	 * case, set this variable with that sub-domain address. */
	$email_message->smtp_exclude_address="";

	/* If you use the direct delivery mode and the GetMXRR is not functional,
	 * you need to use a replacement function. */
	/*
	$_NAMESERVERS=array();
	include("rrcompat.php");
	$email_message->smtp_getmxrr="_getmxrr";
	*/

	/* authentication user name */
	$email_message->smtp_user="";

	/* authentication password */
	$email_message->smtp_password="";

	/* if you need POP3 authetntication before SMTP delivery,
	 * specify the host name here. The smtp_user and smtp_password above
	 * should set to the POP3 user and password*/
	$email_message->smtp_pop3_auth_host="";

	/* authentication realm or Windows domain when using NTLM authentication */
	$email_message->smtp_realm="";

	/* authentication workstation name when using NTLM authentication */
	$email_message->smtp_workstation="";

	/* force the use of a specific authentication mechanism */
	$email_message->smtp_authentication_mechanism="";

	/* Output dialog with SMTP server */
	$email_message->smtp_debug=0;

	/* if smtp_debug is 1,
	 * set this to 1 to make the debug output appear in HTML */
	$email_message->smtp_html_debug=1;

	/* If you use the SetBulkMail function to send messages to many users,
	 * change this value if your SMTP server does not accept sending
	 * so many messages within the same SMTP connection */
	$email_message->maximum_bulk_deliveries=100;

	$email_message->SetEncodedEmailHeader("To",$to_address,$to_name);
	$email_message->SetEncodedEmailHeader("From",$from_address,$from_name);
	$email_message->SetEncodedEmailHeader("Reply-To",$reply_address,$reply_name);
	$email_message->SetHeader("Return-Path",$error_delivery_address);
	$email_message->SetEncodedEmailHeader("Errors-To",$error_delivery_address,$error_delivery_name);
	$email_message->SetEncodedHeader("Subject",$subject);
	$email_message->AddQuotedPrintableTextPart($email_message->WrapText($message));
	$error=$email_message->Send();
	for($recipient=0,Reset($email_message->invalid_recipients);$recipient<count($email_message->invalid_recipients);Next($email_message->invalid_recipients),$recipient++)
		echo "Invalid recipient: ",Key($email_message->invalid_recipients)," Error: ",$email_message->invalid_recipients[Key($email_message->invalid_recipients)],"\n";
	if(strcmp($error,""))
		echo "Error: $error\n";
	else
		echo "Done.\n";
?>