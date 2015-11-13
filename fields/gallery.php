<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Extrafields
 * @since       0.2.0
 *
 * @author      Rene Bentes Pinto <renebentes@yahoo.com.br>
 * @link        http://renebentes.github.io
 * @copyright   Copyright (C) 2015 Rene Bentes Pinto, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

// No direct access.
defined('_JEXEC') or die('Restricted access!');

/**
 * Gallery Field class for the Extrafields.
 *
 * @package    Joomla.Plugin
 * @subpackage System.Extrafields
 * @since      0.2.0
 */
class JFormFieldGallery extends JFormField
{
	/**
   * The form field type.
   *
   * @var   string
   * @since 0.2.0
   */
  public $type = 'Gallery';

  /**
   * Method to get the field input markup.
   *
   * @return  string  The field input markup.
   *
   * @since   0.2.0
   */
  protected function getInput()
  {
		$app        = JFactory::getApplication();
		$params     = JComponentHelper::getParams('com_media');
		$doc        = JFactory::getDocument();
		$content_id = $app->input->get('id', 0);

  	if ($content_id == 0)
  	{
  		$app->enqueueMessage(JText::_('PLG_SYSTEM_EXTRAFIELS_MESSAGE_GALLERY_SAVE_BEFORE_UPLOAD_FILES'), 'warning');
  		return false;
  	}

  	// Load styles
  	$doc->addStyleSheet(JUri::root(true) . '/plugins/system/extrafields/assets/css/jquery.fileupload.css');
		$doc->addStyleSheet(JUri::root(true) . '/plugins/system/extrafields/assets/css/jquery.fileupload-ui.css');

		// Add JavaScript Frameworks.
		JHtml::_('jquery.framework');

		// Load JavaScript.
		$doc->addScript(JUri::root(true) . '/plugins/system/extrafields/assets/js/vendor/jquery.ui.widget.js');
		$doc->addScript(JUri::root(true) . '/plugins/system/extrafields/assets/js/tmpl.min.js');
		$doc->addScript(JUri::root(true) . '/plugins/system/extrafields/assets/js/load-image.all.min.js');
		$doc->addScript(JUri::root(true) . '/plugins/system/extrafields/assets/js/canvas-to-blob.min.js');
		$doc->addScript(JUri::root(true) . '/plugins/system/extrafields/assets/js/jquery.iframe-transport.js');
		$doc->addScript(JUri::root(true) . '/plugins/system/extrafields/assets/js/jquery.fileupload.js');
		$doc->addScript(JUri::root(true) . '/plugins/system/extrafields/assets/js/jquery.fileupload-process.js');
		$doc->addScript(JUri::root(true) . '/plugins/system/extrafields/assets/js/jquery.fileupload-image.js');
		$doc->addScript(JUri::root(true) . '/plugins/system/extrafields/assets/js/jquery.fileupload-validate.js');
		$doc->addScript(JUri::root(true) . '/plugins/system/extrafields/assets/js/jquery.fileupload-ui.js');

		$doc->addScript(JUri::root(true) . '/plugins/system/extrafields/assets/js/main.js');

		$deleteUrl    = 'index.php?option=com_ajax&group=system&plugin=removeFile';
		$saveOrderUrl = 'index.php?option=com_ajax&group=system&plugin=saveOrder';
		$imageUrl     = JUri::root(true) . '/images/content/' . $content_id;

		JHtml::_('sortablelist.sortable', 'galleryList', 'item-form', 'asc', $saveOrderUrl); ?>

    <noscript>
			<link rel="stylesheet" href="<?php echo JUri::root(true); ?>/plugins/system/extrafields/assets/css/jquery.fileupload-noscript.css">
			<link rel="stylesheet" href="<?php echo JUri::root(true); ?>/plugins/system/extrafields/assets/css/jquery.fileupload-ui-noscript.css">
		</noscript>
		<!--[if (gte IE 8)&(lt IE 10)]>
			<script src="<?php echo JUri::root(true); ?>/plugins/system/extrafields/assets/js/cors/jquery.xdr-transport.js"></script>';
		<![endif]-->

		<script>
			jQuery(function($) {
  			'use strict';

  			$('#item-form').fileupload({
    			url: 'index.php?option=com_ajax&group=system&plugin=extrafieldsUpload&format=json',
    			// maxFileSize: <?php echo $params->get('upload_maxsize', 0) * 1024 * 1024; ?>,
    			disableImageResize: /Android(?!.*Chrome)|Opera/.test(window.navigator.userAgent)
  			}).bind('fileuploadsubmit', function(e, data) {
    			var inputs = data.context.find(':input');

    			if (inputs.filter('[required][value=""]').first().focus().length) {
      			return false;
    			}

    			data.formData = inputs.serializeArray();
    			console.log(data);
  			});

			  // Enable iframe cross-domain access via redirect option:
			  $('#item-form').fileupload(
			    'option',
			    'redirect',
			    window.location.href.replace(
			      /\/[^\/]*$/,
			      '/cors/result.html?%s'
			    )
			  );

			  // Load existing files:
			  $('#item-form').addClass('fileupload-processing');

				$.ajax({
		    	url: 'index.php?option=com_ajax&plugin=getFiles',
		    	dataType: 'json',
		    	data: {
		    		content_id: <?php echo $app->input->get('id', 0); ?>,
		    		'<?php echo JSession::getFormToken(); ?>': 1
		    	},
		    	context: $('#item-form')[0]
		  	}).always(function() {
		    	$(this).removeClass('fileupload-processing');
		  	}).done(function(result) {
		    	$(this).fileupload('option', 'done')
		    		.call(this, $.Event('done'), { result: result	});

					var sortableList = new $.JSortableList('#galleryList tbody', 'item-form', 'asc', 'index.php?option=com_ajax&plugin=saveOrder', '', '');
		  	});
			});
		</script>

    <div class="row-fluid fileupload-buttonbar">
      <div class="span7">
        <span class="btn btn-success btn-small fileinput-button">
          <span class="icon-plus"></span><?php echo JText::_('PLG_SYSTEM_EXTRAFIELDS_ARTICLE_FIELD_ADD_FILES_LABEL');?>
          <input type="file" name="files[]" multiple>
          <p class="help-block"><?php echo $params->get('upload_maxsize') == '0' ? JText::_('COM_MEDIA_UPLOAD_FILES_NOLIMIT') : JText::sprintf('COM_MEDIA_UPLOAD_FILES', $params->get('upload_maxsize')); ?></p>
        </span>
        <button type="submit" class="btn btn-primary btn-small start">
          <span class="icon-upload"></span><?php echo JText::_('JTOOLBAR_UPLOAD'); ?>
        </button>
        <button type="reset" class="btn btn-warning btn-small cancel">
          <span class="icon-ban-circle"></span><?php echo JText::_('JTOOLBAR_CANCEL'); ?>
        </button>
        <button type="button" class="btn btn-danger btn-small delete">
          <span class="icon-trash"></span><?php echo JText::_('JTOOLBAR_DELETE'); ?>
        </button>
        <span class="fileupload-process"></span>
      </div>
      <div class="span5 fileupload-progress fade">
        <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">
          <div class="progress-bar progress-bar-success" style="width:0%;"></div>
        </div>
        <div class="progress-extended">&nbsp;</div>
      </div>
    </div>

    <table role="presentation" class="table table-striped table-hove" id="galleryList">
      <thead>
		    <tr>
		      <th width="1%" class="nowrap center hidden-phone"><span class="icon-menu-2"></span></th>
		      <th width="1%" class="hidden-phone"><?php echo JHtml::_('grid.checkall'); ?></th>
		      <th width="5%" class="nowrap"><?php echo JText::_('PLG_SYSTEM_EXTRAFIELDS_ARTICLE_HEADING_PREVIEW'); ?></th>
		      <th class="title"><?php echo JText::_('PLG_SYSTEM_EXTRAFIELDS_ARTICLE_HEADING_TITLE'); ?></th>
		      <th width="5%" class="nowrap hidden-phone"><?php echo JText::_('PLG_SYSTEM_EXTRAFIELDS_ARTICLE_HEADING_SIZE'); ?></th>
		      <th width="5%" class="nowrap"><?php echo JText::_('PLG_SYSTEM_EXTRAFIELDS_ARTICLE_HEADING_ACTION'); ?></th>
		      <th width="1%" class="nowrap center hidden-phone"><?php echo JText::_('JGRID_HEADING_ID');?></th>
		    </tr>
		  </thead>
      <tbody class="files"></tbody>
    </table>

    <script id="template-upload" type="text/x-tmpl">
      {% for (var i=0, file; file=o.files[i]; i++) { %}
		  <tr class="template-upload fade">
		    <td class="order nowrap center hidden-phone">
		      <span class="sortable-handler inactive"><span class="icon-menu"></span></span>
		    </td>
		    <td class="center">
		    	<input type="checkbox" name="cid[]" value="{%=file.id%}" disabled>
		    </td>
		    <td><span class="preview"></span>
		    </td>
		    <td class="nowrap">
		      <p class="name"><input type="text" name="picture[title]" value="{%=file.name%}" class="span6" placeholder="<?php echo JText::_('PLG_SYSTEM_EXTRAFIELDS_ARTICLE_FIELD_TITLE_LABEL'); ?>"></p>
		      <p class="description"><textarea name="picture[description]" cols="30" rows="3" class="span6" placeholder="<?php echo JText::_('PLG_SYSTEM_EXTRAFIELDS_ARTICLE_FIELD_DESCRIPTION_LABEL'); ?>"></textarea></p>
		      <input type="hidden" name="picture[content_id]" value="<?php echo $content_id; ?>">
		      <?php echo JHtml::_('form.token'); ?>
		      <strong class="error text-danger"></strong>
		    </td>
		    <td class="hidden-phone">
		      <p class="size"><?php echo JText::_('PLG_SYSTEM_EXTRAFIELDS_ARTICLE_FIELD_PROCESSING_LABEL'); ?></p>
		      <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar progress-bar-success" style="width:0%;"></div></div>
		    </td>
		    <td class="nowrap">
		    {% if (!i && !o.options.autoUpload) { %}
		      <button class="btn btn-primary start" disabled>
		        <span class="icon-upload"></span><?php echo JText::_('JTOOLBAR_UPLOAD'); ?>
		      </button>
		    {% } %}
		    {% if (!i) { %}
		      <button class="btn btn-warning cancel">
		        <span class="icon-ban-circle"></span><?php echo JText::_('JTOOLBAR_CANCEL'); ?>
		      </button>
		    {% } %}
		    </td>
		    <td class="center hidden-phone"></td>
	    </tr>
		  {% } %}
		</script>

		<script id="template-download" type="text/x-tmpl">
		  {% for (var i=0, file; file=o.files[i]; i++) { %}
      <tr class="template-download fade">
      	<td class="order nowrap center hidden-phone">
        	<span class="sortable-handler inactive"><span class="icon-menu"></span></span>
		      <input type="text" style="display:none" name="order[]" value="{%=file.ordering%}">
	     	</td>
	     	<td class="center">
		    	<input type="checkbox" id="cb{%=i%}" name="cid[]" value="{%=file.id%}" onclick="Joomla.isChecked(this.checked);">
		    </td>
		    <td>
          <span class="preview">
		        <a href="<?php echo $imageUrl; ?>/{%=file.filename%}" title="{%=file.title%}" download="{%=file.filename%}">
		          <img src="<?php echo $imageUrl; ?>/thumbnails/{%=file.filename%}">
		        </a>
          {% if (file.thumbnailUrl) { %}
            <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>
          {% } %}
          </span>
        </td>
        <td>
          <p class="name">
          {% if (file.url) { %}
            <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?'data-gallery':''%}>{%=file.name%}</a>
          {% } else { %}
            <span>{%=file.name%}</span>
          {% } %}
          </p>
          {% if (file.error) { %}
            <div><span class="label label-danger">Error</span> {%=file.error%}</div>
          {% } %}
        </td>
        <td><span class="size">{%=o.formatFileSize(file.size)%}</span></td>
        <td>
        {% if (file.deleteUrl) { %}
          <button class="btn btn-danger delete" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields='{"withCredentials":true}'{% } %}>
            <span class="icon-trash"></span><?php echo JText::_('JTOOLBAR_DELETE'); ?>
          </button>
          <input type="checkbox" name="delete" value="1" class="toggle">
        {% } else { %}
          <button class="btn btn-warning cancel"><span class="icon-ban-circle"></span><?php echo JText::_('JTOOLBAR_CANCEL'); ?></button>
        {% } %}
        </td>
      </tr>
		  {% } %}
		</script>
<?php
  }
}
