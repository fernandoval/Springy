<html>
<head>
<title>{$subject}</title>
<style type="text/css"><!--{literal}
body { color: black ; font-family: arial, helvetica, sans-serif ; background-image: url(http://www.phpclasses.org/graphics/background.gif) ; background-color: #A3C5CC }
A:link, A:visited, A:active { text-decoration: underline }
{/literal}--></style>
</head>
<body>
<center><h1>{$subject}</h1></center>
<hr>
<p>Hello {$firstname},<br>
<br>
This message is just to let you know that the <a href="http://www.phpclasses.org/mimemessage">MIME E-mail message composing and sending PHP class</a> is working as expected.<br>
<br>
Your account balance is ${$balance}.<br>
<br >

Thank you,<br>
{$fromname}</p>
<hr>
<p>You are subscribed with the address {$email} .</p>
</body>
</html>