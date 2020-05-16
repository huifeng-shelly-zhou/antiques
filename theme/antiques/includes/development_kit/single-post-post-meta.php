
<!-- post meta object-->
<table style="width: 100%;  margin-top:20px; border:1px solid #dedede;">
  <tr>
    <td colspan="2">
        <h2>get_post_meta($post_id) object</h2>
        <p>ref: get_post_meta ( int $post_id, string $key = '', bool $single = false )</p>
    </td>
  </tr>
  <?php 
        $postmeta=get_post_meta($post->ID);
        if (!empty($postmeta) && count($postmeta) > 0) :
            foreach ($postmeta as $key=>$value) :
  ?>
              <tr>
                <td><strong style="margin:0 10px;"><?php echo $key; ?></strong></td>
                <td><?php var_dump($value); ?></td>
              </tr>
  <?php 	endforeach;  
        endif;
  ?>
</table>
                    
   		