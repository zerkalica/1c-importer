<div id="content">
<?php foreach ($values as $item) { ?>

  <?php if (is_string($item) ) { ?>
  <div>
  <?php } else { ?>
  <div<?php print cFormatters::attributes($item['attributes']);?>>
  <?php } ?>

    <?php print $item['data'];?>

  </div>

<?php }?>
</div> <!-- content -->
