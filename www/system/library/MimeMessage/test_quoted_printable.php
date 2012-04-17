<?php
/*
 * test_quoted_printable.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/mimemessage/test_quoted_printable.php,v 1.7 2009/07/02 21:35:14 mlemos Exp $
 *
 */

	require("email_message.php");

Function test_quoting(&$message, $test_values, $cs, $quote, $email_header, &$tests, &$failed)
{
	Reset($test_values);
	$end=(GetType($value=Key($test_values))!="string");
	for(;!$end;$tests++)
	{
		echo "Test value \"",$value,"\"...";
		flush();
		$encoded=($quote ? $message->QuoteText($value) : $message->QuotedPrintableEncode($value, $cs, 1, $email_header));
		if(strcmp($encoded,$test_values[$value]))
		{
			echo "\tFAIL: returned \"",$encoded,"\" and not \"",$test_values[$value],"\" as expected!\n";
			$failed++;
		}
		else
			echo "\tOK!\n";
		Next($test_values);
		$end=(GetType($value=Key($test_values))!="string");
	}
}

	$message=new email_message_class;

	$failed=$tests=0;

	echo "Testing header q-encoding...\n\n";
	$cs=$message->default_charset;
	$test_values=array(
		""=>"",
		"Coffee"=>"Coffee",
		"Coffee?"=>"=?".$cs."?q?Coffee=3F?=",
		"More coffee?"=>"More =?".$cs."?q?coffee=3F?=",
		"More coffee, Sir?"=>"More =?".$cs."?q?coffee=2C_Sir=3F?=",
		"?"=>"=?".$cs."?q?=3F?=",
		" ?"=>" =?".$cs."?q?=3F?=",
		" ? "=>" =?".$cs."?q?=3F_?=",
		"\n.Dot.ted"=>"\n\t.Dot.ted",
		"\nFrom line\nfrom line"=>"\n\tFrom line\n\tfrom line",
		"More\ncoffee,\nSir?"=>"=?".$cs."?q?More\n\tcoffee=2C\n\tSir=3F?=",
		"Café?"=>"=?ISO-8859-1?q?Caf=E9=3F?=",
	);
	test_quoting($message, $test_values, $cs, 0, 1, $tests, $failed);

	echo "Testing header q-encoding for non-email address headers...\n\n";
	$cs=$message->default_charset;
	$test_values=array(
		""=>"",
		"Coffee"=>"Coffee",
		"Coffee?"=>"Coffee?",
		"More coffee?"=>"More coffee?",
		"More coffee, Sir?"=>"More coffee, Sir?",
		"?"=>"?",
		" ?"=>" ?",
		" ? "=>" ? ",
		"\n.Dot.ted"=>"\n\t.Dot.ted",
		"\nFrom line\nfrom line"=>"\n\tFrom line\n\tfrom line",
		"More\ncoffee,\nSir?"=>"More\n\tcoffee,\n\tSir?",
		"Café?"=>"=?ISO-8859-1?q?Caf=E9=3F?=",
	);
	test_quoting($message, $test_values, $cs, 0, 0, $tests, $failed);

	echo "\nTesting quoted-printable encoding...\n\n";
	$test_values=array(
		""=>"",
		"Coffee"=>"Coffee",
		"Coffee?"=>"Coffee?",
		"Café"=>"Caf=E9",
		"Café\nau lait"=>"Caf=E9\nau lait",
		"Lait, lait, lait, lait, lait, lait, lait, lait, lait, lait, lait, lait, lait"=>
		"Lait, lait, lait, lait, lait, lait, lait, lait, lait, lait, lait, lait, lai=\nt",
		"Lait, lait, lait, lait, lait, lait, lait,\nlait, lait, lait, lait, lait, lait"=>
		"Lait, lait, lait, lait, lait, lait, lait,\nlait, lait, lait, lait, lait, lait",
		"Café, Café, Café, Café, Café, Café, Café,\nCafé, Café, Café, Café, Café, Café"=>
		"Caf=E9, Caf=E9, Caf=E9, Caf=E9, Caf=E9, Caf=E9, Caf=E9,\nCaf=E9, Caf=E9, Caf=E9, Caf=E9, Caf=E9, Caf=E9",
		"Café, Café, Café, Café, Café, Café, Café, Café, Café Cafe, Café, Café, Café"=>
		"Caf=E9, Caf=E9, Caf=E9, Caf=E9, Caf=E9, Caf=E9, Caf=E9, Caf=E9, Caf=E9 Cafe=\n, Caf=E9, Caf=E9, Caf=E9",
		"Café, Café, Café, Café, Café, Café, Café, Café, Café, Café, Café, Café, Café"=>
		"Caf=E9, Caf=E9, Caf=E9, Caf=E9, Caf=E9, Caf=E9, Caf=E9, Caf=E9, Caf=E9, Caf=\n=E9, Caf=E9, Caf=E9, Caf=E9",
		"Dummy line\n.Dotted.line\n."=>
		"Dummy line\n=2EDotted.line\n=2E",
		"From line\nfrom line\n"=>
		"=46rom line\n=66rom line\n",
		"Very.very.very.very.very.very.very.very.very.very.very.very.very.very.long..text"=>
		"Very.very.very.very.very.very.very.very.very.very.very.very.very.very.long.=\n=2Etext",
		"Café?"=>"Caf=E9?",
	);
	test_quoting($message, $test_values, '', 0, 1, $tests, $failed);

	$test_values=array(
		"S XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX"=>"> S
> XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
> XXXXXXXXXXXXXXXXXXXXXXXXXX
",
	);
	echo "\nTesting quoting text ...\n\n";
	test_quoting($message, $test_values, '', 1, 1, $tests, $failed);

	echo "\nPerformed ",$tests," tests: ",($failed ? $failed." failed, ".($tests-$failed)." succeeded" : "all succeeded"),"!\n";
	
?>