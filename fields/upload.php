<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Extrafields
 * @since       0.3.0
 *
 * @author      Rene Bentes Pinto <renebentes@yahoo.com.br>
 * @link        http://renebentes.github.io
 * @copyright   Copyright (C) 2015 Rene Bentes Pinto, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE
 */

// No direct access.
defined('_JEXEC') or die('Restricted access!');

JFormHelper::loadFieldClass('file');

/**
 * Upload Field class for the Extrafields.
 *
 * @package     Joomla.Plugin
 * @subpackage  System.Extrafields
 * @since       0.3.0
 */
class JFormFieldUpload extends JFormFieldFile
{
	/**
   * The form field type.
   *
   * @var    string
   * @since  0.3.0
   */
  public $type = 'Upload';

  /**
   * The maximum number of files.
   *
   * @var    integer
   * @since  0.3.0
   */
  protected $maxFiles;

  /**
   * Method to get certain otherwise inaccessible properties from the form field object.
   *
   * @param   string  $name  The property name for which to the the value.
   *
   * @return  mixed          The property value or null.
   *
   * @since   0.3.0
   */
  public function __get($name)
  {
    switch ($name)
    {
      case 'maxfiles':
        return $this->$name;
    }

    return parent::__get($name);
  }

  /**
   * Method to set certain otherwise inaccessible properties of the form field object.
   *
   * @param   string  $name   The property name for which to the the value.
   * @param   mixed   $value  The value of the property.
   *
   * @return  void
   *
   * @since   0.3.0
   */
  public function __set($name, $value)
  {
    switch ($name)
    {
      case 'maxfiles':
        $this->$name = (string) $value;
        break;

      default:
        parent::__set($name, $value);
    }
  }

  /**
   * Method to attach a JForm object to the field.
   *
   * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
   * @param   mixed             $value    The form field value to validate.
   * @param   string            $group    The field name group control value. This acts as as an array container for the field.
   *                                      For example if the field has name="foo" and the group value is set to "bar" then the
   *                                      full field name would end up being "bar[foo]".
   *
   * @return  boolean                     True on success.
   *
   * @see     JFormField::setup()
   * @since   0.3.0
   */
  public function setup(SimpleXMLElement $element, $value, $group = null)
  {
     $return = parent::setup($element, $value, $group);

    if ($return)
    {
      $this->maxFiles = $this->element['maxfiles'] < ini_get('max_file_uploads') ? $this->element['maxfiles'] : ini_get('max_file_uploads');
    }

    return $return;
  }

  /**
   * Method to get the field input markup.
   *
   * @return  string  The field input markup.
   *
   * @since   0.3.0
   */
  protected function getInput()
  {
  	// Define variables
		$app        = JFactory::getApplication();
		$params     = JComponentHelper::getParams('com_media');
		$doc        = JFactory::getDocument();
		$content_id = $app->input->get('id', 0);
		$html       = array();
		$script     = array();

		// Initialize some field attributes.
		$accept    = !empty($this->accept) ? ' accept="' . $this->accept . '"' : '';
		$size      = !empty($this->size) ? ' size="' . $this->size . '"' : '';
		$class     = !empty($this->class) ? ' class="' . $this->class . '"' : '';
		$disabled  = $this->disabled || $content_id == 0 ? ' disabled' : '';
		$required  = $this->required ? ' required aria-required="true"' : '';
		$autofocus = $this->autofocus ? ' autofocus' : '';
		$multiple  = $this->multiple ? ' multiple' : '';

		// Initialize JavaScript field attributes.
		$onchange = $this->onchange ? ' onchange="' . $this->onchange . '"' : '';

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

		$script[] = '  jQuery(function($) {';
  	$script[] = '    \'use strict\';';
  	$script[] = '';
  	$script[] = '    $(\'#item-form\').fileupload({';
    $script[] = '      url: \'index.php?option=com_ajax&group=system&plugin=extrafieldsUpload&format=json\',';
    $script[] = '      maxFileSize: ' . $params->get('upload_maxsize', 0) * 1024 * 1024 . ',';
  	$script[] = '      maxNumberOfFiles: ' . $this->maxFiles . ',';
    $script[] = '      disableImageResize: /Android(?!.*Chrome)|Opera/.test(window.navigator.userAgent)';
  	$script[] = '    }).bind(\'fileuploadsubmit\', function(e, data) {';
    $script[] = '      var inputs = data.context.find(\':input\');';
    $script[] = '';
    $script[] = '      if (inputs.filter(\'[required][value=""]\').first().focus().length) {';
    $script[] = '        return false;';
    $script[] = '      }';
    $script[] = '';
    $script[] = '      data.formData = inputs.serializeArray();';
  	$script[] = '    });';
		$script[] = '';
		$script[] = '    // Enable iframe cross-domain access via redirect option:';
		$script[] = '    $(\'#item-form\').fileupload(';
		$script[] = '      \'option\',';
		$script[] = '      \'redirect\',';
		$script[] = '      window.location.href.replace(';
		$script[] = '        /\/[^\/]*$/,';
		$script[] = '        \'/cors/result.html?%s\'';
		$script[] = '      )';
		$script[] = '    );';
		$script[] = '';
		$script[] = '    // Load existing files:';
		$script[] = '    $(\'#item-form\').addClass(\'fileupload-processing\');';
		$script[] = '';
		$script[] = '    $.ajax({';
		$script[] = '      url: \'index.php?option=com_ajax&plugin=getFiles\',';
		$script[] = '      dataType: \'json\',';
		$script[] = '      data: {';
		$script[] = '        content_id: ' . $app->input->get('id', 0) . ',';
		// $script[] = '        ' . JSession::getFormToken() . ': 1';
		$script[] = '      },';
		$script[] = '      context: $(\'#item-form\')[0]';
		$script[] = '    }).always(function() {';
		$script[] = '      $(this).removeClass(\'fileupload-processing\');';
		$script[] = '    }).done(function(result) {';
		$script[] = '      $(this).fileupload(\'option\', \'done\')';
		$script[] = '        .call(this, $.Event(\'done\'), { result: result });';
		$script[] = '';
		$script[] = '      var sortableList = new $.JSortableList(\'#' . $this->name . 'List tbody\', \'item-form\', \'asc\', \'index.php?option=com_ajax&plugin=saveOrder\', \'\', \'\');';
		$script[] = '    });';
		$script[] = '  });';

		$doc->addScriptDeclaration(implode("\n", $script));

		$deleteUrl    = 'index.php?option=com_ajax&group=system&plugin=removeFile';
		$saveOrderUrl = 'index.php?option=com_ajax&group=system&plugin=saveOrder';
		$imageUrl     = JUri::root(true) . '/images/content/' . $content_id;

		JHtml::_('sortablelist.sortable', 'galleryList', 'item-form', 'asc', $saveOrderUrl);

		$html[] = '<noscript>';
		$html[] = '  <link rel="stylesheet" href="' . JUri::root(true) . '/plugins/system/extrafields/assets/css/jquery.fileupload-noscript.css">';
		$html[] = '  <link rel="stylesheet" href="' . JUri::root(true) . '/plugins/system/extrafields/assets/css/jquery.fileupload-ui-noscript.css">';
		$html[] = '</noscript>';
		$html[] = '<!--[if (gte IE 8)&(lt IE 10)]>';
		$html[] = '  <script src="' . JUri::root(true) . '/plugins/system/extrafields/assets/js/cors/jquery.xdr-transport.js"></script>';
		$html[] = '<![endif]-->';

		if ($content_id == 0)
  	{
  		$html[] = '<div class="alert alert-warning">';
  		$html[] = '  <strong>' . JText::_('WARNING') . '</strong> ' . JText::_('PLG_SYSTEM_EXTRAFIELDS_MESSAGE_SAVE_BEFORE_UPLOAD_PHOTOS');
  		$html[] = '</div>';
  	}

		$html[] = '<div class="row-fluid fileupload-buttonbar">';
    $html[] = '  <div class="span7">';
    $html[] = '    <span class="btn btn-default btn-small fileinput-button">';
    $html[] = '      <span class="icon-plus"></span>' . JText::_('PLG_SYSTEM_EXTRAFIELDS_ARTICLE_FIELD_ADD_FILES_LABEL');
    $html[] = '        <input type="file" name="' . $this->name . '" id="' . $this->id . '"' . $accept . $disabled . $autofocus . $multiple . '>';
    $html[] = '    </span>';
    $html[] = '    <button type="submit" class="btn btn-success btn-small start">';
    $html[] = '      <span class="icon-upload"></span>' . JText::_('JTOOLBAR_UPLOAD');
    $html[] = '    </button>';
    $html[] = '    <button type="reset" class="btn btn-warning btn-small cancel">';
    $html[] = '      <span class="icon-ban-circle icon-white"></span>' . JText::_('JTOOLBAR_CANCEL');
    $html[] = '    </button>';
    $html[] = '    <button type="button" class="btn btn-danger btn-small delete">';
    $html[] = '      <span class="icon-trash"></span>' . JText::_('JTOOLBAR_DELETE');
    $html[] = '    </button>';
    $html[] = '    <p class="help-block">' . ($params->get('upload_maxsize') == '0' ? JText::_('PLG_SYSTEM_EXTRAFIELDS_MESSAGE_UPLOAD_FILES_NOLIMIT') : JText::sprintf('PLG_SYSTEM_EXTRAFIELDS_MESSAGE_UPLOAD_FILES_LIMIT', $params->get('upload_maxsize'))) . '</p>';
    $html[] = '    <p class="help-block">' . ($this->maxFiles == '0' ? JText::_('PLG_SYSTEM_EXTRAFIELDS_MESSAGE_UPLOAD_MAXFILES_NOLIMIT') : JText::sprintf('PLG_SYSTEM_EXTRAFIELDS_MESSAGE_UPLOAD_MAXFILES_LIMIT', $this->maxFiles)) . '</p>';
    $html[] = '    <span class="fileupload-process"></span>';
    $html[] = '  </div>';
    $html[] = '  <div class="span5 fileupload-progress fade">';
    $html[] = '    <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100">';
    $html[] = '      <div class="progress-bar progress-bar-success" style="width:0%;"></div>';
    $html[] = '    </div>';
    $html[] = '    <div class="progress-extended">&nbsp;</div>';
    $html[] = '  </div>';
    $html[] = '</div>';

    $html[] = '<table role="presentation" class="table table-striped table-hove" id="' . $this->fieldname . 'List">';
    $html[] = '  <thead>';
		$html[] = '    <tr>';
		$html[] = '      <th width="1%" class="nowrap center hidden-phone"><span class="icon-menu-2"></span></th>';
		$html[] = '      <th width="1%" class="hidden-phone">' . JHtml::_('grid.checkall') . '</th>';
		$html[] = '      <th width="5%" class="nowrap">' . JText::_('PLG_SYSTEM_EXTRAFIELDS_ARTICLE_HEADING_PREVIEW') . '</th>';
		$html[] = '      <th class="title">' . JText::_('PLG_SYSTEM_EXTRAFIELDS_ARTICLE_HEADING_TITLE') . '</th>';
		$html[] = '      <th width="5%" class="nowrap hidden-phone">' . JText::_('PLG_SYSTEM_EXTRAFIELDS_ARTICLE_HEADING_SIZE') . '</th>';
		$html[] = '      <th width="5%" class="nowrap">' . JText::_('PLG_SYSTEM_EXTRAFIELDS_ARTICLE_HEADING_ACTION') . '</th>';
		$html[] = '      <th width="1%" class="nowrap center hidden-phone">' . JText::_('JGRID_HEADING_ID') . '</th>';
		$html[] = '    </tr>';
		$html[] = '  </thead>';
    $html[] = '  <tbody class="files"></tbody>';
    $html[] = '</table>';

    $html[] = '<script id="template-upload" type="text/x-tmpl">';
    $html[] = '  {% for (var i=0, file; file=o.files[i]; i++) { %}';
		$html[] = '  <tr class="template-upload fade">';
		$html[] = '    <td class="order nowrap center hidden-phone">';
		$html[] = '      <span class="sortable-handler inactive"><span class="icon-menu"></span></span>';
		$html[] = '    </td>';
		$html[] = '    <td class="center">';
		$html[] = '      <input type="checkbox" name="cid[]" value="{%=file.id%}" disabled>';
		$html[] = '    </td>';
		$html[] = '    <td><span class="preview"></span></td>';
		$html[] = '    <td class="nowrap">';
		$html[] = '      <p class="name"><input type="text" name="picture[title]" value="{%=file.name%}" class="span6" placeholder="' . JText::_('PLG_SYSTEM_EXTRAFIELDS_ARTICLE_FIELD_TITLE_LABEL') . '"></p>';
		$html[] = '      <p class="description"><textarea name="picture[description]" cols="30" rows="3" class="span6" placeholder="' . JText::_('PLG_SYSTEM_EXTRAFIELDS_ARTICLE_FIELD_DESCRIPTION_LABEL') . '"></textarea></p>';
		$html[] = '      <input type="hidden" name="picture[content_id]" value="' . $content_id . '">';
		$html[] = '      ' . JHtml::_('form.token');
		$html[] = '      <strong class="error text-danger"></strong>';
		$html[] = '    </td>';
		$html[] = '    <td class="hidden-phone">';
		$html[] = '      <p class="size">' . JText::_('PLG_SYSTEM_EXTRAFIELDS_ARTICLE_FIELD_PROCESSING_LABEL') .' </p>';
		$html[] = '      <div class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0"><div class="progress-bar progress-bar-success" style="width:0%;"></div></div>';
		$html[] = '    </td>';
		$html[] = '    <td class="nowrap">';
		$html[] = '    {% if (!i && !o.options.autoUpload) { %}';
		$html[] = '      <button class="btn btn-sucess start" disabled>';
		$html[] = '        <span class="icon-upload"></span>' . JText::_('JTOOLBAR_UPLOAD');
		$html[] = '      </button>';
		$html[] = '    {% } %}';
		$html[] = '    {% if (!i) { %}';
		$html[] = '      <button class="btn btn-warning cancel">';
		$html[] = '        <span class="icon-ban-circle icon-white"></span>' . JText::_('JTOOLBAR_CANCEL');
		$html[] = '      </button>';
		$html[] = '    {% } %}';
		$html[] = '    </td>';
		$html[] = '    <td class="center hidden-phone"></td>';
	  $html[] = '  </tr>';
		$html[] = '  {% } %}';
		$html[] = '</script>';

		$html[] = '<script id="template-download" type="text/x-tmpl">';
		$html[] = '  {% for (var i=0, file; file=o.files[i]; i++) { %}';
    $html[] = '  <tr class="template-download fade">';
    $html[] = '    <td class="order nowrap center hidden-phone">';
    $html[] = '      <span class="sortable-handler inactive"><span class="icon-menu"></span></span>';
		$html[] = '      <input type="text" style="display:none" name="order[]" value="{%=file.ordering%}">';
	  $html[] = '    </td>';
	  $html[] = '    <td class="center">';
		$html[] = '      <input type="checkbox" id="cb{%=i%}" name="cid[]" value="{%=file.id%}" onclick="Joomla.isChecked(this.checked);">';
		$html[] = '    </td>';
		$html[] = '    <td>';
    $html[] = '      <span class="preview">';
		$html[] = '        <a href="<?php echo $imageUrl; ?>/{%=file.filename%}" title="{%=file.title%}" download="{%=file.filename%}">';
		$html[] = '          <img src="<?php echo $imageUrl; ?>/thumbnails/{%=file.filename%}">';
		$html[] = '        </a>';
    $html[] = '        {% if (file.thumbnailUrl) { %}';
    $html[] = '          <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" data-gallery><img src="{%=file.thumbnailUrl%}"></a>';
    $html[] = '        {% } %}';
    $html[] = '      </span>';
    $html[] = '    </td>';
    $html[] = '    <td>';
    $html[] = '      <p class="name">';
    $html[] = '      {% if (file.url) { %}';
    $html[] = '        <a href="{%=file.url%}" title="{%=file.name%}" download="{%=file.name%}" {%=file.thumbnailUrl?\'data-gallery\':\'\'%}>{%=file.name%}</a>';
    $html[] = '      {% } else { %}';
    $html[] = '        <span>{%=file.name%}</span>';
    $html[] = '      {% } %}';
    $html[] = '      </p>';
    $html[] = '      {% if (file.error) { %}';
    $html[] = '        <div><span class="label label-danger">Error</span> {%=file.error%}</div>';
    $html[] = '      {% } %}';
    $html[] = '    </td>';
    $html[] = '    <td><span class="size">{%=o.formatFileSize(file.size)%}</span></td>';
    $html[] = '    <td>';
    $html[] = '    {% if (file.deleteUrl) { %}';
    $html[] = '      <button class="btn btn-danger delete" data-type="{%=file.deleteType%}" data-url="{%=file.deleteUrl%}"{% if (file.deleteWithCredentials) { %} data-xhr-fields=\'{"withCredentials":true}\'{% } %}>';
    $html[] = '        <span class="icon-trash"></span>' . JText::_('JTOOLBAR_DELETE');
    $html[] = '      </button>';
    $html[] = '      <input type="checkbox" name="delete" value="1" class="toggle">';
    $html[] = '    {% } else { %}';
    $html[] = '      <button class="btn btn-warning cancel"><span class="icon-ban-circle"></span>' . JText::_('JTOOLBAR_CANCEL') . '</button>';
    $html[] = '    {% } %}';
    $html[] = '    </td>';
    $html[] = '  </tr>';
		$html[] = '  {% } %}';
		$html[] = '</script>';

    return implode("\n", $html);
  }
}
