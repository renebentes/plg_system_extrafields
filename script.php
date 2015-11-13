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

/**
 * Script file of Extrafields Plugin
 *
 * @package    Joomla.Plugin
 * @subpackage System.Extrafields
 *
 * @since      0.1.0
 */
class PlgSystemExtrafieldsInstallerScript
{
	/**
	 * Extension name
	 *
	 * @var   string
	 * @since 0.1.0
	 */
	private $_extension = 'plg_system_extrafields';

	/**
	 * Version release
	 *
	 * @var   string
	 * @since 0.1.0
	 */
	private $_release = '';

	/**
	 * Array of obsolete files and folders.
	 * Examples:
	 *    /path/to/file.ext
	 *    /path/to/folder
	 *
	 * @var   array
	 * @since 0.1.0
	 */
	private $_obsoletes = array(
		'files'   => array(
			'/plugins/system/extrafields/forms/content.xml',
			'/plugins/system/extrafields/assets/js/jquery.ui.widget.js',
			'/plugins/system/extrafields/assets/js/jquery.postmessage-transport.js',
			'/plugins/system/extrafields/assets/js/jquery.xdr-transport.js',
			'/plugins/system/extrafields/assets/js/main.js',
			'/plugins/system/extrafields/fields/gallery.php'
		),
		'folders' => array(
			'/plugins/system/extrafields/overrides/administrator/components/com_content/views/articleextra',
			'/media/plg_system_extrafields'
		)
	);

	/**
	 * Method to install the plugin
	 *
	 * @param  JAdapterInstance $adapter The object responsible for running this script.
	 *
	 * @return boolean True on success.
	 *
	 * @since  0.1.0
	 */
	public function install(JAdapterInstance $adapter)
	{
		JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_SYSTEM_EXTRAFIELDS_INSTALL_TEXT', $this->_extension, $this->_release));
	}

	/**
	 * Method to uninstall the plugin
	 *
	 * @param  JAdapterInstance $adapter The object responsible for running this script.
	 *
	 * @return boolean True on success.
	 *
	 * @since  0.1.0
	 */
	public function uninstall(JAdapterInstance $adapter)
	{
		JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_SYSTEM_EXTRAFIELDS_UNINSTALL_TEXT', $this->_extension, $this->_release));
	}

	/**
	 * Method to update the plugin
	 *
	 * @param  JAdapterInstance $adapter The object responsible for running this script.
	 *
	 * @return boolean True on success.
	 *
	 * @since  0.1.0
	 */
	public function update(JAdapterInstance $adapter)
	{
		JFactory::getApplication()->enqueueMessage(JText::sprintf('PLG_SYSTEM_EXTRAFIELDS_UPDATE_TEXT', $this->_extension, $this->_release));
	}

	/**
	 * Method to run before an install/update/uninstall method
	 *
	 * @param  string           $route   Which action is happening (install|uninstall|discover_install).
	 * @param  JAdapterInstance $adapter The object responsible for running this script.
	 *
	 * @return boolean True on success.
	 *
	 * @since  0.1.0
	 */
	public function preflight($route, JAdapterInstance $adapter)
	{
		if(!$this->_checkCompatible($route, $adapter))
		{
			return false;
		}
	}

	/**
	 * Method to run after an install/update/uninstall method
	 *
	 * @param  string           $route   Which action is happening (install|uninstall|discover_install).
	 * @param  JAdapterInstance $adapter The object responsible for running this script.
	 *
	 * @return boolean True on success.
	 *
	 * @since  0.1.0
	 */
	public function postflight($route, JAdapterInstance $adapter)
	{
		if ($route != 'install')
		{
			$this->_removeObsoletes();
		}

		if ($route != 'uninstall')
		{
			// Call _enableExtension() method
			$this->_enableExtension($adapter->get('name'));
		}
	}

	/**
	 * Method for checking compatibility installation environment
	 *
	 * @param  JAdapterInstance $adapter The object responsible for running this script.
	 *
	 * @return boolean True if the installation environment is compatible
	 *
	 * @since  0.1.0
	 */
	private function _checkCompatible($route, JAdapterInstance $adapter)
	{
		// Get the application.
		$this->_release = (string) $adapter->get('manifest')->version;
		$min_version    = (string) $adapter->get('manifest')->attributes()->version;
		$jversion       = new JVersion;

		if (version_compare($jversion->getShortVersion(), $min_version, 'lt' ))
		{
			JFactory::getApplication()->enquequeMessage(JText::sprintf('PLG_SYSTEM_EXTRAFIELDS_VERSION_UNSUPPORTED', $this->_extension, $this->_release, $min_version), 'error');
			return false;
		}

		// Storing old release number for process in postflight.
		if ($route == 'update')
		{
			$oldRelease = $this->_getParam('version');

			if (version_compare($this->_release, $oldRelease, 'lt'))
			{
				JFactory::getApplication()->enquequeMessage(JText::sprintf('PLG_SYSTEM_EXTRAFIELDS_UPDATE_UNSUPPORTED', $this->_extension, $oldRelease, $this->_release), 'error');
				return false;
			}
		}

		return true;
	}

	/**
	 * Removes obsoletes files and folders
	 *
	 * @return void
	 *
	 * @since  0.1.0
	 */
	private function _removeObsoletes()
	{
		if (!empty($this->_obsoletes['files']))
		{
			jimport('joomla.filesystem.file');

			foreach($this->_obsoletes['files'] as $file)
			{
				$file = JPATH_ROOT . (substr($file, 0, 1) == '/' ? $file : '/' . $file);
				if(JFile::exists($file) && !JFile::delete($file))
				{
					JFactory::getApplication()->enqueueMessage(JText::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $file), 'error');
				}
			}
		}

		if (!empty($this->_obsoletes['folders']))
		{
			jimport('joomla.filesystem.folder');

			foreach($this->_obsoletes['folders'] as $folder)
			{
				$folder = JPATH_ROOT . (substr($folder, 0, 1) == '/' ? $folder : '/' . $folder);
				if(JFolder::exists($folder) && !JFolder::delete($folder))
				{
					JFactory::getApplication()->enqueueMessage(JText::sprintf('FILES_JOOMLA_ERROR_FILE_FOLDER', $folder), 'error');
				}
			}
		}
	}

	/**
	 * Get a variable from the manifest cache.
	 *
	 * @param  string $name Column name
	 *
	 * @return string Value of column name
	 *
	 * @since  0.1.0
	 */
	private function _getParam($name)
	{
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		$query->select($db->quoteName('manifest_cache'));
		$query->from($db->quoteName('#__extensions'));
		$query->where($db->quoteName('name') . ' = ' . $db->quote($this->_extension));
		$db->setQuery($query);

		$manifest = json_decode($db->loadResult(), true);

		return $manifest[$name];
	}

	/**
	 * Enable Extrafields Plugin
	 *
	 * @param  string $name The name of extension to enable
	 *
	 * @return void
	 *
	 * @since  0.1.0
	 */
	private function _enableExtension($name)
	{
		// Initialiase variables.
		$db    = JFactory::getDbo();
		$query = $db->getQuery(true);

		// Create the base update statement.
		$query->update($db->quoteName('#__extensions'));
		$query->set($db->quoteName('enabled') . ' = ' . $db->quote('1'));
		$query->where($db->quoteName('name') . ' = ' . $db->quote($name));

		// Set the query and execute the update.
		$db->setQuery($query);

		try
		{
			$db->execute();
		}
		catch (RuntimeException $e)
		{
			JFactory::getApplication()->enqueueMessage($e->getMessage(), 'warning');
		}

	}
}
