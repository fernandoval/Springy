<?php
/*
 * qmail_message.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/mimemessage/qmail_message.php,v 1.11 2009/07/27 22:07:23 mlemos Exp $
 *
 *
 */

/*
{metadocument}<?xml version="1.0" encoding="ISO-8859-1"?>
<class>

	<package>net.manuellemos.mimemessage</package>

	<name>qmail_message_class</name>
	<version>@(#) $Id: qmail_message.php,v 1.11 2009/07/27 22:07:23 mlemos Exp $</version>
	<copyright>Copyright © (C) Manuel Lemos 2001-2004</copyright>
	<title>MIME E-mail message composing and sending using Qmail</title>
	<author>Manuel Lemos</author>
	<authoraddress>mlemos-at-acm.org</authoraddress>

	<documentation>
		<idiom>en</idiom>
		<purpose>Implement an alternative message delivery method using
			<link>
				<data>Qmail</data>
				<url>http://www.qmail.org/</url>
			</link> MTA (Mail Transfer Agent).</purpose>
		<usage>This class should be used exactly the same way as the base
			class for composing and sending messages. Just create a new object of
			this class as follows and set only the necessary variables to
			configure details of delivery using Qmail.<paragraphbreak />
			<tt>require('email_message.php');<br />
			require('qmail_message.php');<br />
			<br />
			$message_object = new qmail_message_class;<br /></tt><paragraphbreak />
		</usage>
	</documentation>

{/metadocument}
*/

class qmail_message_class extends email_message_class
{
	/* Private variables */

	var $line_break="\n";

	/* Public variables */

/*
{metadocument}
	<variable>
		<name>qmail_path</name>
		<type>STRING</type>
		<value>/var/qmail/bin</value>
		<documentation>
			<purpose>Specifying the path of the Qmail programs.</purpose>
			<usage>Usually the default is correct.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $qmail_path="/var/qmail/bin";

/*
{metadocument}
	<variable>
		<name>mailer_delivery</name>
		<value>qmail $Revision: 1.11 $</value>
		<documentation>
			<purpose>Specify the text that is used to identify the mail
				delivery class or sub-class. This text is appended to the
				<tt>X-Mailer</tt> header text defined by the
				mailer variable.</purpose>
			<usage>Do not change this variable.</usage>
		</documentation>
	</variable>
{/metadocument}
*/
	var $mailer_delivery='qmail $Revision: 1.11 $';

	Function SendMail($to,$subject,$body,$headers,$return_path)
	{
		$command=$this->qmail_path."/qmail-inject";
		if(strcmp($return_path,""))
			$command.=" '".preg_replace("/'/", "'\\''","-f$return_path")."'";
		if(!($pipe=@popen($command,"w")))
			return($this->OutputPHPError("it was not possible to open qmail-inject input pipe", $php_errormsg));
		if(strlen($headers))
			$headers.="\n";
		if(!@fputs($pipe,"To: ".$to."\nSubject: ".$subject."\n".$headers."\n")
		|| !@fputs($pipe,$body)
		|| !@fflush($pipe))
			return($this->OutputPHPError("it was not possible to write qmail-inject input pipe", $php_errormsg));
		return(($rc=(pclose($pipe)>>8)) ? "qmail-inject error ".$rc : "");
	}

	Function SendMailing($mailing)
	{
		if(strlen($this->error))
			return($this->error);
		if(!IsSet($this->mailings[$mailing]))
			return($this->OutputError("it was not specified a valid mailing"));
		if(IsSet($this->mailings[$mailing]["Envelope"]))
			return($this->OutputError("the mailing was not yet ended"));
		$this->ResetMessage();
		$base_path=$this->mailings[$mailing]["BasePath"];
		if(GetType($header_lines=@File($base_path.".h"))!="array")
			return($this->OutputPHPError("could not read the mailing headers file ".$base_path.".h", $php_errormsg));
		if(!($envelope_file=@fopen($base_path.".e","rb")))
			return($this->OutputPHPError("could not open the mailing envelope file ".$base_path.".e", $php_errormsg));
		for($return_path=$data="";!feof($envelope_file) || strlen($data);)
		{
			if(GetType($break=strpos($data,chr(0)))!="integer")
			{
				if(GetType($chunk=@fread($envelope_file,$this->file_buffer_length))!="string")
				{
					fclose($envelope_file);
					return($this->OutputPHPError("could not read the mailing envelop file ".$base_path.".e", $php_errormsg));
				}
				$data.=$chunk;
				continue;
			}
			if($break==0)
				break;
			switch($data[0])
			{
				case "F":
					$return_path=substr($data,1,$break-1);
					break;
				default:
					return($this->OutputError("invalid mailing envelope file ".$base_path.".e"));
			}
			break;
		}
		fclose($envelope_file);
		if(strlen($return_path)==0)
			return($this->OutputError("envelope file ".$base_path.".e does not contain a return path"));
		$headers=$this->FormatHeader("Date",gmdate("D, d M Y H:i:s -0000"))."\n";
		for($has=array(),$line=0;$line<count($header_lines);$line++)
		{
			$header=$this->Tokenize($header_lines[$line],":");
			switch(strtolower($header))
			{
				case "return-path":
				case "bcc":
				case "date":
				case "content-length":
					break;
				case "from":
				case "to":
				case "cc":
				case "message-id":
					$has[strtolower($header)]=1;
				default:
					$headers.=$this->FormatHeader($header,trim($this->Tokenize("\n")))."\n";
					break;
			}
		}
		if(!IsSet($has["from"]))
			$headers.=$this->FormatHeader("From","<".$return_path.">")."\n";
		if(!IsSet($has["to"])
		&& !IsSet($has["cc"]))
			$headers.=$this->FormatHeader("Cc","recipient list not shown: ;")."\n";
		if(!IsSet($has["message-id"]))
		{
			$micros=$this->Tokenize(microtime()," ");
			$seconds=$this->Tokenize("");
			$this->Tokenize($return_path,"@");
			$host=$this->Tokenize("@");
			if($host[strlen($host)-1]=="-")
				$host=substr($host,0,strlen($host)-1);
			$headers.=$this->FormatHeader("Message-ID","<".strftime("%Y%m%d%H%M%S",$seconds).substr($micros,1,5).".qmail@".$host.">")."\n";
		}
		if(!($body_file=@fopen($base_path.".b","rb")))
			return($this->OutputPHPError("could not open the mailing body file ".$base_path.".b", $php_errormsg));
		for($body="";!feof($body_file);)
		{
			if(GetType($chunk=@fread($body_file,$this->file_buffer_length))!="string")
			{
				fclose($body_file);
				return($this->OutputPHPError("could not read the mailing body file ".$base_path.".b", $php_errormsg));
			}
			$body.=$chunk;
		}
		fclose($body_file);
		$command=$this->qmail_path."/qmail-queue 1<".$base_path.".e";
		if(!($pipe=@popen($command,"w")))
			return($this->OutputPHPError("it was not possible to open qmail-queue input pipe", $php_errormsg));
		if(!@fputs($pipe,$headers."\n".$body)
		|| !@fflush($pipe))
			return($this->OutputPHPError("it was not possible to write qmail-queue input pipe", $php_errormsg));
		return(($rc=(pclose($pipe)>>8)) ? "qmail-queue error ".$rc : "");
	}
};

/*

{metadocument}
</class>
{/metadocument}

*/

?>