<?php
/*
 * pickup_message.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/mimemessage/pickup_message.php,v 1.4 2006/05/04 01:24:35 mlemos Exp $
 *
 *
 */

/*
{metadocument}<?xml version="1.0" encoding="ISO-8859-1"?>
<class>

	<package>net.manuellemos.mimemessage</package>

	<name>pickup_message_class</name>
	<version>@(#) $Id: pickup_message.php,v 1.4 2006/05/04 01:24:35 mlemos Exp $</version>
	<copyright>Copyright ¿ (C) Manuel Lemos 1999-2004</copyright>
	<title>MIME E-mail message composing and sending using a Windows mail
		server pickup directory</title>
	<author>Manuel Lemos</author>
	<authoraddress>mlemos-at-acm.org</authoraddress>

	<documentation>
		<idiom>en</idiom>
		<purpose>Implement an alternative message delivery method by dropping
			messages in a Windows mail server pickup directory, thus overriding
			the method of using the PHP <tt>mail()</tt> function implemented by
			the base class.<paragraphbreak />
			It is meant to be used by on Windows 2000 or later with IIS or
			Exchange mail servers because since this release the pickup directory
			started being supported.<paragraphbreak />
			It is much faster than relaying messages to an SMTP server because
			it works simply by storing messages in a special directory. This
			delivery method does not have the overhead of the SMTP protocol. The
			class does not need to wait for the mail server to pickup the
			messages and deliver them to the destination recipients. Therefore,
			it is recommended for bulk mailing.</purpose>
		<usage>This class should be used exactly the same way as the base
			class for composing and sending messages. Just create a new object of
			this class as follows and set only the necessary variables to
			configure details of the message pickup.<paragraphbreak />
			<tt>require('email_message.php');<br />
			require('pickup_message.php');<br />
			<br />
			$message_object = new pickup_message_class;<br /></tt><paragraphbreak />
			<b>- Requirements</b><paragraphbreak />
			You need to use at least Windows 2000 with IIS mail server or
			Exchange 2000 or later.<paragraphbreak />
			The PHP script using this class must also run in the same Windows
			machine on which the mail server is running. The current user must
			have sufficient privileges to write to the mail server pickup
			directory.<paragraphbreak />
			<b>- Pickup directory</b><paragraphbreak />
			Before sending a message you need set the
			<variablelink>mailroot_directory</variablelink> variable to specify
			the path of the mail server directory, so the class knows where the
			messages must be dropped for subsequent pickup and delivery by the
			mail server.</usage>
	</documentation>

{/metadocument}
*/

class pickup_message_class extends email_message_class
{
	/* Private variables */

	var $line_break="\r\n";
	var $pickup_file=0;
	var $pickup_file_name="";

	/* Public variables */

/*
{metadocument}
	<variable>
		<name>mailroot_directory</name>
		<type>STRING</type>
		<value></value>
		<documentation>
			<purpose>Specify the path of the directory where the <tt>Pickup</tt>
				sub-directory is located. This sub-directory is used by the mail
				server to pickup the messages to deliver.</purpose>
			<usage>If this variable is set to an empty string, the class attempts
				to locate the directory automatically checking the registry.<paragraphbreak />
				If the class is not able to determine the mailroot directory path
				and you are certain that IIS or Exchange programs are installed in
				your Windows 2000 or later machine, set this variable to the
				correct path of your mail server root directory.<paragraphbreak />
				Usually it is located inside the <tt>Inetpub</tt> directory of IIS
				or Exchange installation path, but it may also be located in a
				slightly different path.</usage>
			<example><tt><stringvalue>C:\Inetpub\mailroot\</stringvalue></tt></example>
		</documentation>
	</variable>
{/metadocument}
*/
	var $mailroot_directory="";

/*
{metadocument}
	<variable>
		<name>mailer_delivery</name>
		<value>pickup $Revision: 1.4 $</value>
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
	var $mailer_delivery='pickup $Revision: 1.4 $';

	Function CleanupMessageFile()
	{
		if($this->pickup_file)
		{
			fclose($this->pickup_file);
			$this->pickup_file=0;
			unlink($this->pickup_file_name);
		}
	}

	Function StartSendingMessage()
	{
		if(strlen($this->mailroot_directory)==0)
		{
			if(function_exists("class_exists")
			&& class_exists("COM"))
			{
				$shell=new COM("WScript.Shell");
				$wwwroot=$shell->RegRead("HKEY_LOCAL_MACHINE\\SOFTWARE\\Microsoft\\InetStp\\PathWWWRoot");
				if(is_dir($wwwroot))
				{
					$mailroot=$wwwroot."\\..\\mailroot";
					if(is_dir($mailroot."\\Pickup"))
						$this->mailroot_directory=$mailroot;
					else
					{
						$mailroot=$wwwroot."\\mailroot";
						if(is_dir($mailroot."\\Pickup"))
							$this->mailroot_directory=$mailroot;
					}
				}
			}
		}
		if(strlen($this->mailroot_directory)==0)
			return($this->OutputError("it was not specified the mailroot directory path"));
		if(!is_dir($this->mailroot_directory."\\Pickup"))
			return($this->OutputError("the specified mailroot path ".$this->mailroot_directory." does not contain a Pickup directory"));
		$this->pickup_file_name=tempnam(GetEnv("TMP"),"eml");
		if(!($this->pickup_file=@fopen($this->pickup_file_name,"w")))
			return($this->OutputPHPError("could not create a pickup message file ".$this->pickup_file_name, $php_errormsg));
		return("");
	}

	Function SendMessageHeaders($headers)
	{
		for($error=$header_data="",$message_id_set=$date_set=0,$header=0,$return_path=$sender=$receivers=array(),Reset($headers);$header<count($headers);$header++,Next($headers))
		{
			$header_name=Key($headers);
			switch(strtolower($header_name))
			{
				case "return-path":
					$return_path[$headers[$header_name]]=1;
					break;
				case "from":
					$error=$this->GetRFC822Addresses($headers[$header_name],$sender);
					break;
				case "to":
				case "cc":
				case "bcc":
					$this->GetRFC822Addresses($headers[$header_name],$receivers);
					break;
				case "date":
					$date_set=1;
					break;
				case "message-id":
					$message_id_set=1;
					break;
			}
			if(strlen($error))
			{
				$this->CleanupMessageFile();
				return($this->OutputError($error));
			}
			switch(strtolower($header_name))
			{
				case "x-sender":
				case "x-receiver":
				case "bcc":
					break;
				default:
					$header_data.=$this->FormatHeader($header_name,$headers[$header_name])."\r\n";
			}
		}
		if(count($sender)==0)
		{
			$this->CleanupMessageFile();
			return($this->OutputError("it was not specified a valid From header"));
		}
		if(count($receivers)==0)
		{
			$this->CleanupMessageFile();
			return($this->OutputError("it was not specified a valid To header"));
		}
		Reset($return_path);
		Reset($sender);
		$envelop="x-sender: ".(count($return_path) ? Key($return_path) : Key($sender)).$this->line_break;
		for($receiver=0,Reset($receivers);$receiver<count($receivers);Next($receivers),$receiver++)
			$envelop.="x-receiver: ".Key($receivers).$this->line_break;
		if(!$date_set)
			$header_data.="Date: ".date("D, d M Y H:i:s T").$this->line_break;
		if(!$message_id_set
		&& $this->auto_message_id)
		{
			$sender=(count($return_path) ? Key($return_path) : Key($sender));
			$header_data.=$this->GenerateMessageID($sender).$this->line_break;
		}
		if(!@fputs($this->pickup_file, $envelop.$header_data.$this->line_break))
		{
			$this->CleanupMessageFile();
			return($this->OutputPHPError("could not write the message headers to the pickup file", $php_errormsg));
		}
		return("");
	}

	Function SendMessageBody($data)
	{
		if(!@fputs($this->pickup_file, $data))
		{
			$this->CleanupMessageFile();
			return($this->OutputPHPError("could not write the message body to the pickup file", $php_errormsg));
		}
		return("");
	}

	Function EndSendingMessage()
	{
		if(!@fflush($this->pickup_file))
			return($this->OutputPHPError("could not flush the message body to the pickup file", $php_errormsg));
		fclose($this->pickup_file);
		$this->pickup_file=0;
		$pickup_file_path=$this->mailroot_directory;
		if(strcmp(substr($pickup_file_path,strlen($pickup_file_path)-1,1),"\\"))
			$pickup_file_path.="\\";
		$pickup_file_path.="Pickup\\";
		if(!@copy($this->pickup_file_name,$pickup_file_path.basename($this->pickup_file_name)))
			$error=$this->OutputPHPError("could not copy the message file to the pickup directory", $php_errormsg);
		else
			$error="";
		unlink($this->pickup_file_name);
		return($error);
	}

	Function StopSendingMessage()
	{
		return("");
	}

};

/*

{metadocument}
</class>
{/metadocument}

*/

?>