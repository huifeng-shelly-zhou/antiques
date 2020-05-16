                   
<!-- post terms object-->
<table style="width: 100%;  margin-top:20px; border:1px solid #dedede;">
  <tr>
    <td colspan="2">
        <h2>get_the_terms(int|object $post, string $taxonomy) object</h2>
    </td>
  </tr>  
  <?php 
        
		$all_taxonomies = get_taxonomies();
		if (count ($all_taxonomies) >0) {
			unset($all_taxonomies['nav_menu']);
			unset($all_taxonomies['link_category']);
			unset($all_taxonomies['post_format']);
		}
		
        if (isset($all_taxonomies) && !empty($all_taxonomies) ):	  		
        foreach ($all_taxonomies as $t) :
  ?>                          
          <tr style="margin:5px 0;">
            <td colspan="2">
                <strong>get_the_terms($post->ID, <span style="color:#9A0002"><?php echo $t;?></span>)</strong>
            </td>
          </tr>
          <?php 
                $postterm=get_the_terms($post->ID, $t);
                if (!empty($postterm) && count($postterm) > 0) :
                    foreach ($postterm as $key=>$value) :
              ?>
              <tr>
                <td><strong style="margin:0 10px;"><?php echo $key; ?></strong></td>
                <td><?php var_dump($value); ?></td>
              </tr>
              <?php 
                    endforeach;  
                endif;
          ?>              

  <?php endforeach; 
    endif;
   ?>
               
</table>
            
                      
    