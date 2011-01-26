<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
  "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?php print $lang;?>" lang="<?php print $lang;?>">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php print $charset;?>" />
	<title><?php print $title;?></title>
  <?php print $css;?>
  <?php print $js;?>
  <?php print $js_init;?>
</head>

<body<?php print ($js_init ? ' onload="init()"' : '');?>>
	<div id="header">
		<?php print $header;?>
	</div> <!-- header -->

	<div id="container">
     <?php print $messages;?>
			<?php print $content;?>
	</div> <!-- container -->


  <div id="footer">
			<?php print $footer;?>

	</div> <!-- footer -->

</body>


</html>
