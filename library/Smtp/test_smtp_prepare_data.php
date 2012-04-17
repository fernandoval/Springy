<?php
/*
 * test_smtp_prepare_data.php
 *
 * @(#) $Header: /home/mlemos/cvsroot/smtp/test_smtp_prepare_data.php,v 1.1 2003/08/26 07:39:59 mlemos Exp $
 *
 */


	require("smtp.php");

Function ReferencePrepareData($data)
{
 	$length=strlen($data);
	for($output="",$position=0;$position<$length;)
	{
		$next_position=$length;
		for($current=$position;$current<$length;$current++)
		{
			switch($data[$current])
			{
				case "\n":
					$next_position=$current+1;
					break 2;
				case "\r":
					$next_position=$current+1;
					if($next_position<$length
					&& $data[$next_position]=="\n")
						$next_position++;
					break 2;
			}
		}
		if($data[$position]==".")
			$output.=".";
		$output.=substr($data,$position,$current-$position);
		if($current<$length)
			$output.="\r\n";
		$position=$next_position;
	}
	return($output);
}


	$smtp=new smtp_class;
	$test_data=array(
		"Empty    "=>"",
		"Dot      "=>".",
		"CR       "=>"\r",
		"LF       "=>"\n",
		"Double LF"=>"\n\n",
		"Double CR"=>"\r\r",
		"Triple LF"=>"\n\n\n",
		"Triple CR"=>"\r\r\r",
		"Four LF  "=>"\n\n\n\n",
		"Four CR  "=>"\r\r\r\r",
		"Complex  "=>"\n1\n\n2\r3\n4\n\r5\r\n.\n."
	);
	Reset($test_data);
	$end=(GetType($test=Key($test_data))!="string");
	for($passed=$failed=0,$failed_tests="";!$end;)
	{
		echo "Testing ",$test," ...";
		flush();
		$reference_prepared_data=ReferencePrepareData($test_data[$test]);
		$smtp->PrepareData($test_data[$test],$preg_prepared_data,1);
		$smtp->PrepareData($test_data[$test],$ereg_prepared_data,0);
		$preg_ok=!strcmp($reference_prepared_data,$preg_prepared_data);
		$ereg_ok=!strcmp($reference_prepared_data,$ereg_prepared_data);
		if($preg_ok && $ereg_ok)
		{
			echo " OK";
			$passed++;
		}
		else
		{
			if($failed)
				$failed_tests.=", ";
			$failed_tests.=trim($test);
			$failed++;
			echo " FAILED!\n";
			echo "Test data \"",str_replace("\r","\\r",str_replace("\n","\\n\n",$test_data[$test])),"\"\n";
			echo "Reference prepared data \"",str_replace("\r","\\r",str_replace("\n","\\n\n",$reference_prepared_data)),"\"\n";
			if(!$preg_ok)
				echo "preg prepared data \"",str_replace("\r","\\r",str_replace("\n","\\n\n",$preg_prepared_data)),"\"\n";
			if(!$ereg_ok)
				echo "ereg prepared data \"",str_replace("\r","\\r",str_replace("\n","\\n\n",$ereg_prepared_data)),"\"\n";
		}
		echo "\n";

		Next($test_data);
		$end=(GetType($test=Key($test_data))!="string");
	}
	if($failed==0)
		echo "All ",$passed," tests passed!\n";
	else
		echo "Passed ",$passed," tests, failed ",$failed,": ",$failed_tests,"!\n";

 ?>
