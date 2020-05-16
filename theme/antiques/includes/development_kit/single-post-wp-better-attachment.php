<!-- attachments-->
<table style="width: 100%; margin-top:20px; border:1px solid #dedede;">
  <tr>
    <td colspan="2"><h2>Use WP Better Attachement Plugin Functions</h2></td>
  </tr>
  <tr>
    <td><h2>Function</h2></td>
    <td><h2>Result</h2></td>
  </tr>
  <tr>
    <td><strong style="margin:0 10px;">Checks for post attachments</strong>
      <p>wpba_attachments_exist( array )</p>
      <ul>
        <li>post_id =&gt; current post id <strong>Will retrieve attachments from the passed ID if available</strong></li>
        <li>show_post_thumbnail =&gt; true</li>
      </ul>
      <p> returns boolean</p></td>
    <td>
        <?php
            $attachments=wpba_attachments_exist();
            var_dump($attachments);
        ?>
    </td>
  </tr>
  <tr>
    <td><strong style="margin:0 10px;">Get post attachments</strong>
      <p>wpba_get_attachments( array )</p>
      <ul>
        <li>post_id =&gt; current post id <strong>Will retrieve attachments from the passed ID if available</strong></li>
        <li>show_post_thumbnail =&gt; true</li>
      </ul>
      <p> returns object</p></td>
    <td>
        <?php
            $attachments=wpba_get_attachments();
            var_dump($attachments);
        ?>
    </td>
  </tr>
  <tr>
    <td><strong style="margin:0 10px;">Retrieves an array of attachments</strong>
      <p>wpba_attachment_list( array )</p>
      <ul>
        <li>post_id="current_post_id"</li>
        <li>show_icon="false"</li>
        <li>file_type_categories="image,file,audio,video"</li>
        <li>file_extensions="png,pdf" <strong>Array of file extensions, defaults to WordPress allowed attachment types (get_allowed_mime_types())</strong></li>
        <li>image_icon="path/to/directory/image-icon.png"</li>
        <li>file_icon="path/to/directory/file-icon.png"</li>
        <li>audio_icon="path/to/directory/audio-icon.png"</li>
        <li>video_icon="path/to/directory/video-icon.png"</li>
        <li>icon_size="16,20" <strong>width, height</strong></li>
        <li>use_attachment_page="true"</li>
        <li>use_caption_for_title="false"</li>
        <li>open_new_window="true"</li>
        <li>show_post_thumbnail="true"</li>
        <li>no_attachments_msg="Sorry, no attachments exist."</li>
        <li>wrap_class="wpba wpba-wrap"</li>
        <li>list_class="unstyled"</li>
        <li>list_id="wpba_attachment_list"</li>
        <li>list_item_class="wpba-list-item pull-left"</li>
        <li>link_class="wpba-link pull-left"</li>
        <li>icon_class="wpba-icon pull-left"</li>
      </ul>
      <p> returns string</p></td>
    <td>
        <?php
            $attachments=wpba_attachment_list();
            var_dump($attachments);
        ?>
    </td>
  </tr>
 
</table>