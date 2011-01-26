  <script type="text/javascript">
    function init() {

<?php foreach ($values as $item) if ($item['params']!==FALSE) { ?>
      <?php print $item['init'] .  '(' . json_encode($item['params']) . ');' ;?>

<?php } ?>
    }
  </script>
