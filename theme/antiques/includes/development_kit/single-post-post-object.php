<!-- post object-->
<table style="width: 100%;  margin-top:20px; border:1px solid #dedede;">
  <tr>
    <td colspan="2"><h2>$post object</h2></td>
  </tr>
  <?php foreach ($post as $key=>$value) : ?>
  <tr>
    <td><strong style="margin:0 10px;"><?php echo $key; ?></strong></td>
    <td><?php var_dump($value); ?></td>
  </tr>
  <?php endforeach;  ?>
</table>
            