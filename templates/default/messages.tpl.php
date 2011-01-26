<ul id="messages">
<?php foreach ($values as $item) { ?>
  <li<?php print cFormatters::attributes($item['attributes']);?>><?php print $item['value'];?></li>

<?php }?>
</ul> <!-- messages -->
