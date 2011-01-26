<table<?php print cFormatters::attributes($attributes);?>>
<thead>
  <tr>
    <?php foreach($header as $key => $value) { ?>
      <th class="<?php print $key;?>"><?php print $value;?></th>
    <?php }?>

  </tr>
</thead>
<tbody>
<?php foreach ($rows as $i => $row) { ?>
  <tr class="<?php print ($i % 2) ? 'odd' : 'even'; ?>">
    <?php foreach($row as $key => $value) { ?>
      <td class="<?php print $key;?>"><?php print $value;?></td>
    <?php }?>
  </tr>
<?php }?>
</tbody>
</table>
