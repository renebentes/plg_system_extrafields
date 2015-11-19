<?php
/**
 * @package    Joomla.Plugin
 * @subpackage System.Extrafields
 * @since      0.1.0
 *
 * @author     Rene Bentes Pinto <renebentes@yahoo.com.br>
 * @link       http://renebentes.github.io
 * @copyright  Copyright (C) 2015 Rene Bentes Pinto, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

// No direct access.
defined('_JEXEC') or die('Restricted access!');

use Joomla\Registry\Registry;

/**
 * Extrafields System plugin
 *
 * @package     Joomla.Plugin
 * @subpackage  System.Extrafields
 * @since       0.1.0
 */
class PlgSystemExtrafields extends JPlugin
{
	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 * @since  0.1.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * Method called before a JForm is rendered. It can be used to modify the JForm
	 * object in memory before rendering.
	 *
	 * @param   JForm   $form  The form to be altered.
	 * @param   object  $data  An object containing the data for the form.
	 *
	 * @return  boolean        True on success, false otherwise
	 *
	 * @since   0.1.0
	 */
	public function onContentPrepareForm($form, $data)
	{
		if (!($form instanceof JForm))
		{
			$this->_subject->setError('JERROR_NOT_A_FORM');
			return false;
		}

		if ($form->getName() == 'com_categories.categorycom_content' || $form->getName() == 'com_content.article')
		{
			jimport('joomla.filesystem.folder');
			jimport('joomla.filesystem.file');

			$path = __DIR__ . '/forms';

			if (!is_dir($path))
			{
				return false;
			}

			$files = JFolder::files($path, '.xml');
			if (!$files || !count($files))
			{
				return false;
			}

			$extras = array();
			foreach ($files as $file)
			{
				$extras[] = JFile::stripExt($file);
			}

			if (count($extras))
			{

				if ($form->getName() == 'com_categories.categorycom_content')
				{
					$line   = array();
					$line[] = '<?xml version="1.0" encoding="utf-8"?>';
					$line[] = '<form>';
					$line[] = '  <fields name="params">';
					$line[] = '    <fieldset';
					$line[] = '      name="extrafields" label="PLG_SYSTEM_EXTRAFIELDS_CATEGORY_FIELDSET_EXTRAFIELDS_LABEL"';
					$line[] = '      description="PLG_SYSTEM_EXTRAFIELDS_CATEGORY_FIELDSET_EXTRAFIELDS_DESC"';
					$line[] = '    >';
					$line[] = '      <field';
					$line[] = '        name="group"';
					$line[] = '        type="list"';
					$line[] = '        label="PLG_SYSTEM_EXTRAFIELDS_FIELD_GROUP_LABEL"';
					$line[] = '        description="PLG_SYSTEM_EXTRAFIELDS_FIELD_GROUP_DESC"';
					$line[] = '        default=""';
					$line[] = '      >';
					$line[] = '        <option value="">JNONE</option>';

					foreach ($extras as $extra)
					{
						if ($extra !== 'extrafields')
						{
							$line[] = '        <option value="' . $extra . '">PLG_SYSTEM_EXTRAFIELDS_FIELD_GROUP_OPTION_' . strtoupper($extra) . '</option>';
						}
					}

					$line[] = '      </field>';
					$line[] = '    </fieldset>';
					$line[] = '  </fields>';
					$line[] = '</form>';

					$xml = simplexml_load_string(implode("\n", $line));
					$form->load($xml, false);
				}
				else
				{
					$app = JFactory::getApplication();
					$fdata = empty($data) ? $app->input->post->get('jform', array(), 'array') : (is_object($data) ? $data->getProperties() : $data);
					$catid = $app->input->getInt('catid', $app->getUserState('com_content.articles.filter.category_id'));

					if(!$catid && is_array($fdata) && !empty($fdata))
					{
						$catid = $fdata['catid'];
					}

					// Load default extra fields
					JForm::addFormPath($path);
					$form->loadFile('extrafields', false);

					if($catid)
					{
						$categories = JCategories::getInstance('Content', array('countItems' => 0 ));
						$category   = $categories->get($catid);
						$params     = $category->params;
						if(!$params instanceof JRegistry)
						{
							$params = new JRegistry;
							$params->loadString($category->params);
						}

						if($params instanceof JRegistry)
						{
							$extrafile = $path . '/' . $params->get('group') . '.xml';
							if(is_file($extrafile))
							{
								$form->loadFile($params->get('group'), false);
							}
						}
					}
				}
			}
		}

		return true;
	}

	/**
	 * Method for upload and show files on server
	 *
	 * @return  JSON            The json result
	 */
	public function onAjaxExtrafields()
	{
		jimport('joomla.filesystem.file');
		jimport('joomla.filesystem.folder');

		$app         = JFactory::getApplication();
		$params      = JComponentHelper::getParams('com_media');
		$response    = array();
		$user        = JFactory::getUser();
		$mediaHelper = new JHelperMedia;
		$paramName   = $app->input->get('paramName', 'files');

		// Check for request forgeries.
		if (!JSession::checkToken('request'))
		{
			$response[$paramName] = array(
				'error'  => JText::_('JINVALID_TOKEN')
			);

			echo json_encode($response);
			return;
		}

		switch ($app->input->server->getString('REQUEST_METHOD', 'GET'))
		{
			case 'OPTIONS':
      case 'HEAD':
      	$this->_head($app->input);
        break;
      case 'GET':
        $this->_get($app->input);
        break;
      case 'PATCH':
      case 'PUT':
      case 'POST':
        $this->_post($app->input);
        break;
      case 'DELETE':
        $this->_delete();
        break;
      default:
        header('HTTP/1.1 405 Method Not Allowed');
		}

		return;


		JLog::addLogger(array('text_file' => 'upload.error.php'), JLog::ALL, array('upload'));

		// Get some data from the request.
		$files   = $app->input->files->get($paramName, '', 'array');
		//$picture = $app->input->post->get('picture', '', 'array');

		foreach ($files as $file)
		{
			if ($_SERVER['CONTENT_LENGTH'] > ($params->get('upload_maxsize', 0) * 1024 * 1024)
				|| $_SERVER['CONTENT_LENGTH'] > (int) (ini_get('upload_max_filesize')) * 1024 * 1024
				|| $_SERVER['CONTENT_LENGTH'] > (int) (ini_get('post_max_size')) * 1024 * 1024
				|| $_SERVER['CONTENT_LENGTH'] > (int) (ini_get('memory_limit')) * 1024 * 1024)
			{
				$response['files'] = array(
					'error' => JText::_('JLIB_MEDIA_ERROR_WARNFILETOOLARGE')
				);

				return json_encode($response);
			}/*

			// Set FTP credentials, if given.
			JClientHelper::setCredentialsFromRequest('ftp');

			// Make the filename safe.
			$filename = uniqid() . '.' . JFile::getExt($file['name']);

			if (isset($file['name']))
			{
				// The request is valid.
				$err = null;

				$folder = '/images/content/' . $picture['content_id'];
				$thumbs = $folder . '/thumbnails';

				if (!JFolder::exists($folder) &&  !JFolder::create($folder))
				{
					$app->enqueueMessage(JText::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $folder), 'error');
					$err = JText::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $folder);
				}

				if (!JFolder::exists($thumbs) &&  !JFolder::create($thumbs))
				{
					$app->enqueueMessage(JText::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $thumbs), 'error');
					$err = JText::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $thumbs);
				}

				$filepath = JPath::clean($folder . '/' . $filename);


				if (!$mediaHelper->canUpload($file, 'com_media') || !$err)
				{
					//JLog::add('Invalid: ' . $filepath . ': ' . $err, JLog::INFO, 'upload');

					$response->files[] = array(
						'status' => '0',
						'error' => JText::_($err)
					);

					echo json_encode($response);
					return;
				}

				$object_file           = new JObject($file);
				$object_file->filepath = $filepath;

				if (JFile::exists($object_file->filepath))
				{
					// File exists.
					JLog::add('File exists: ' . $object_file->filepath . ' by user_id ' . $user->id, JLog::INFO, 'upload');

					$response->files[] = array(
						'status' => '0',
						'error'  => JText::_('COM_MEDIA_ERROR_FILE_EXISTS')
					);

					echo json_encode($response);
					return;
				}
				elseif (!$user->authorise('core.create', 'com_media'))
				{
					// File does not exist and user is not authorised to create.
					JLog::add('Create not permitted: ' . $object_file->filepath . ' by user_id ' . $user->id, JLog::INFO, 'upload');

					$response->files[] = array(
						'status' => '0',
						'error'  => JText::_('COM_MEDIA_ERROR_CREATE_NOT_PERMITTED')
					);

					echo json_encode($response);
					return;
				}

				if (!JFile::upload($object_file->tmp_name, $object_file->filepath))
				{
					// Error in upload.
					JLog::add('Error on upload: ' . $object_file->filepath, JLog::INFO, 'upload');

					$response->files[] = array(
						'status' => '0',
						'error'  => JText::_('COM_MEDIA_ERROR_UNABLE_TO_UPLOAD_FILE')
					);

					echo json_encode($response);
					return;
				}
				else
				{
					// Load the parameters.
					/*$params = JComponentHelper::getParams('com_gallery');

					$max_size = explode('x', $params->get('max_size', '1280x720'));
					$thumb_size = explode('x', $params->get('thumb_size', '280x280'));

					$JImage = new JImage($object_file->filepath);

					try
					{
						$image = $JImage->cropResize($max_size[0], $max_size[1], false);
						$image->toFile($object_file->filepath);

						$thumbnail = $JImage->cropResize($thumb_size[0], $thumb_size[1], false);
						$thumbnail->toFile($folder . '/thumbnails/' . $filename);
					}
					catch (Exception $e)
					{
						$app->enqueueMessage($e->getMessage(), 'error');
					}*//*

					JLog::add($picture['content_id'], JLog::INFO, 'upload');

					$db    = JFactory::getDbo();
					$query = $db->getQuery(true);
					$query->select($db->quoteName('MAX(ordering)'))
						->from($db->quoteName('#__content_file'))
						->where($db->quoteName('content_id') . ' = ' . $picture['content_id']);
					$db->setQuery($query);

					$fields = array();
					$values = array();
					$data   = array(
						'content_id'  => $picture['content_id'],
						'title'       => JFile::stripExt($picture['title']),
						'description' => $picture['description'],
						'filename'    => $filename,
						'size'        => $file['size'],
						'type'        => $file['type'],
						'state'       => 1,
						'ordering'    => $db->loadResult() + 1,
						'featured'		=> 0
					);
					foreach ($data as $key => $value)
					{
						$fields[] = $db->quoteName($key);
						$values[] = $db->quote($value);
					}

					$query = $db->getQuery(true);
					$query->insert($db->quoteName('#__content_file'))
						->columns($fields)
						->values($values);
					$db->setQuery($query);

					if (!$db->execute())
					{
						$response->files[] = array(
							'status' => '0',
							'error'  => JText::_('COM_MEDIA_ERROR_BAD_REQUEST')
						);

						echo json_encode($response);
						return;
					}

					/*$query = $db->getQuery(true);
					$query->select($db->quoteName('*'))
						->from($db->quoteName('#__content_file'))*/

					/*$response->files[] = array(
						'status'      => '1',
						'error'       => JText::sprintf('COM_MEDIA_UPLOAD_COMPLETE', substr($object_file->filepath, strlen(JPATH_ROOT)))/*,
						'id'          => $item->id,
						'title'       => $item->title,
						'description' => $item->description,
						'filename'    => $item->filename,
						'size'        => $item->size,
						'ordering'    => $item->ordering*//*
					);

					echo json_encode($response);
					return;
				}
			}
			else
			{
				$response->files[] = array(
					'status' => '0',
					'error'  => JText::_('COM_MEDIA_ERROR_BAD_REQUEST')
				);

				echo json_encode($response);
				return;
			}*/
		}
	}

	/**
	 * Set headers to page
	 *
	 * @param   JInput  $input  The JInput object
	 *
	 * @return  void
	 *
	 * @since   0.3.0
	 */
	private function _head(JInput $input)
	{
		header('Pragma: no-cache');
    header('Cache-Control: no-store, no-cache, must-revalidate');
    header('Content-Disposition: inline; filename="files.json"');
    header('X-Content-Type-Options: nosniff');
    header('Vary: Accept');
    if (strpos($input->server->getString('HTTP_ACCEPT', ''), 'application/json') !== false)
    {
        header('Content-type: application/json');
    } else {
        header('Content-type: text/plain');
    }
	}

	private function _get(JInput $input)
	{
		echo json_encode($input->get('paramName'));
		return;
	}

	private function _post(JInput $input)
	{
		echo json_encode($input->get('paramName'));
		return;
	}
}
