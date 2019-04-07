<?php

namespace Core;

class FileAccessException extends \Exception {}
class InvalidPathException extends \Exception {}

class File
{
	/**
	 * Create a file
	 *
	 * @param string $basepath Dir where it will be placed
	 * @param string $name Filename
	 * @param string $contents
	 * @param string $overwrite
	 * @return int|bool Number of bytes written or false in failure
	 * @throws InvalidPathException
	 * @throws FileAccessException
	 */
	public static function create($basepath, $name, $contents = null, $overwrite = false)
	{
		$new_file = rtrim($basepath, '\\/') . DS . $name;

		if ( ! is_dir($basepath) or ! is_writable($basepath))
		{
			throw new InvalidPathException('Invalid basepath "' . Debug::cleanPath($basepath) . '" - cannot create file at this location.');
		}
		elseif (is_file($new_file) and ! $overwrite)
		{
			throw new FileAccessException('File "' . Debug::cleanPath($new_file) . '" exists already, cannot be created.');
		}
		elseif($overwrite and is_file($new_file) and ! is_writable($new_file))
		{
			throw new FileAccessException('File "' . Debug::cleanPath($new_file) . '" is not writable!');
		}

		return file_put_contents($new_file, $contents, LOCK_EX);
	}

	/**
	 * Append content to a file. If it does'nt exist, create.
	 *
	 * @param string $basepath Dir where it will be placed
	 * @param string $name Filename
	 * @param string $contents
	 * @return int|bool Number of bytes written or false in failure
	 * @throws InvalidPathException
	 */
	public static function append($basepath, $name, $contents = null)
	{
		$new_file = rtrim($basepath, '\\/') . DS . $name;

		if ( ! is_dir($basepath) or ! is_writable($basepath))
		{
			throw new InvalidPathException('Invalid basepath "' . Debug::cleanPath($basepath) . '" - cannot create file at this location.');
		}

		return file_put_contents($new_file, $contents, LOCK_EX | FILE_APPEND);
	}

	/**
	 * Create an empty directory
	 *
	 * @param string $name
	 * @param int $chmod
	 * @return bool
	 * @throws FileAccessException
	 */
	public static function createDir($name, $chmod = null)
	{
		if (is_dir($name))
			throw new FileAccessException('Directory "' . Debug::cleanPath($name) . '" exists already, cannot be created.');

		is_null($chmod) and $chmod = Config::get('system.file_folders_chmod', 0777);
		$recursive = (strpos($name, '/') !== false or strpos($name, '\\') !== false);

		return mkdir($name, $chmod, $recursive);
	}

	/**
	 * Read file
	 *
	 * @param string $path
	 * @return string|bool
	 * @throws InvalidPathException
	 */
	public static function read($path)
	{
		if( ! is_file($path))
			throw new InvalidPathException('Cannot read file "' . Debug::cleanPath($path) . '" - file does not exists.');

		return file_get_contents($path);
	}

	/**
	 * List all files and folders inside a directory
	 *
	 * @param string $path Dirname
	 * @param int $depth How many levels you want to search
	 * @return array
	 * @throws InvalidPathException
	 * @throws FileAccessException
	 */
	public static function listDir($path, $depth = 0)
	{
		$path = rtrim($path, '/\\');

		if ( ! is_dir($path))
			throw new InvalidPathException('Invalid path "' . Debug::cleanPath($path) . '" - directory cannot be read.');

		if ( ! $fp = opendir($path))
			throw new FileAccessException('Could not open directory "' . Debug::cleanPath($path) . '" for reading.');

		$files      = array();
		$dirs       = array();
		$new_depth  = $depth - 1;

		while (false !== ($file = readdir($fp)))
		{
			// Remove '.', '..'
			if (in_array($file, array('.', '..')))
				continue;

			if (is_dir($path . DS . $file))
			{
				// Use recursion when depth not depleted or not limited...
				if ($depth < 1 or $new_depth > 0)
				{
					$dirs[$file . DS] = static::listDir($path . DS . $file . DS, $new_depth);
				}
				// ... or set dir to false when not read
				else
				{
					$dirs[$file . DS] = false;
				}
			}
			else
			{
				$files[] = $file;
			}
		}

		closedir($fp);

		// sort dirs & files naturally and return array with dirs on top and files
		uksort($dirs, 'strnatcasecmp');
		natcasesort($files);
		return array_merge($dirs, $files);
	}

	/**
	 * Get the octal permissions for a file or directory
	 *
	 * @param string $path
	 * @return string Octal permissions
	 * @throws InvalidPathException
	 */

	/**
	 * Get the octal permissions for a file or directory
	 *
	 * @param string $path
	 * @return string
	 * @throws InvalidPathException
	 */
	public static function getPermissions($path)
	{
		if ( ! file_exists($path))
			throw new InvalidPathException('Path "' . Debug::cleanPath($path) . '" is not a directory or a file, cannot get permissions.');

		return substr(sprintf('%o', fileperms($path)), -4);
	}

	/**
	 * Get the last modified time
	 *
	 * @param string $path
	 * @return int Unix timestamp
	 * @throws InvalidPathException
	 */
	public static function getModifiedTime($path)
	{
		if ( ! file_exists($path))
			throw new InvalidPathException('Path "' . Debug::cleanPath($path) . '" is not a directory or a file, cannot get creation timestamp.');

		return filemtime($path);
	}

	/**
	 * Get the file created time
	 *
	 * @param string $path
	 * @return int Unix timestamp
	 * @throws InvalidPathException
	 */
	public static function getCreatedTime($path)
	{
		if ( ! file_exists($path))
			throw new InvalidPathException('Path "' . Debug::cleanPath($path) . '" is not a directory or a file, cannot get creation timestamp.');

		return filectime($path);
	}

	/**
	 * Get a file's size in bytes.
	 *
	 * @param string $path
	 * @return int Unix timestamp
	 * @throws InvalidPathException
	 */
	public static function getSize($path)
	{
		if ( ! file_exists($path))
			throw new InvalidPathException('Path "' . Debug::cleanPath($path) . '" is not a directory or file, cannot get size.');

		return filesize($path);
	}

	/**
	 * Rename (or move) directory or file
	 *
	 * @param string $path
	 * @param string $new_path
	 * @return bool
	 * @throws InvalidPathException
	 */
	public static function rename($path, $new_path)
	{
		if ( ! file_exists($path))
			throw new InvalidPathException('Cannot rename file or dir "' . Debug::cleanPath($path) . '" - given path does not exist.');

		return rename($path, $new_path);
	}

	/**
	 * Copy a single file
	 *
	 * @param string $path
	 * @param string $new_path
	 * @return bool
	 * @throws InvalidPathException
	 * @throws FileAccessException
	 */
	public static function copyFile($path, $new_path)
	{
		if ( ! is_file($path))
			throw new InvalidPathException('Cannot copy file "' . Debug::cleanPath($path) . '": given path is not a file.');
		elseif (file_exists($new_path))
			throw new FileAccessException('Cannot copy file "' . Debug::cleanPath($path) . '": new path "' . Debug::cleanPath($new_path) . '" already exists.');

		return copy($path, $new_path);
	}

	/**
	 * Recursively copy directory contents to another directory.
	 *
	 * @param string $source
	 * @param string $destination
	 * @param int $options \FilesystemIterator options
	 * @throws InvalidPathException
	 */
	public static function copyDir($source, $destination, $options = \FilesystemIterator::SKIP_DOTS)
	{
		if ( ! is_dir($source))
			throw new InvalidPathException('Source "' . Debug::cleanPath($source) . '" is not a directory.');

		// First we need to create the destination directory if it doesn't
		// already exists. This directory hosts all of the assets we copy
		// from the installed bundle's source directory.
		if ( ! is_dir($destination))
			mkdir($destination, 0777, true);

		$items = new \FilesystemIterator($source, $options);

		foreach ($items as $item)
		{
			$location = $destination . DS . $item->getBasename();

			// If the file system item is a directory, we will recurse the
			// function, passing in the item directory. To get the proper
			// destination path, we'll add the basename of the source to
			// to the destination directory.
			if ($item->isDir())
			{
				$path = $item->getRealPath();

				static::copyDir($path, $location, $options);
			}
			// If the file system item is an actual file, we can copy the
			// file from the bundle asset directory to the public asset
			// directory. The "copy" method will overwrite any existing
			// files with the same name.
			else
			{
				copy($item->getRealPath(), $location);
			}
		}
	}

	/**
	 * Create a new symlink
	 *
	 * @param string $path Target
	 * @param string $link_path Link name
	 * @return bool
	 * @throws InvalidPathException
	 * @throws FileAccessException
	 */
	public static function symlink($path, $link_path)
	{
		if ( ! file_exists($path))
			throw new InvalidPathException('Cannot symlink: given path "' . Debug::cleanPath($path) . '" does not exist.');
		elseif (file_exists($link_path))
			throw new FileAccessException('Cannot symlink: link path "' . Debug::cleanPath($link_path) . '" already exists.');

		return symlink($path, $link_path);
	}

	/**
	 * Delete file
	 *
	 * @param string $path
	 * @return bool
	 * @throws InvalidPathException
	 */
	public static function delete($path)
	{
		if ( ! is_file($path))
			throw new InvalidPathException('Cannot delete file: given path "' . Debug::cleanPath($path) . '" is not a file.');

		return unlink($path);
	}

	/**
	 * Recursively delete a directory.
	 *
	 * @param string $directory
	 * @return bool
	 * @throws InvalidPathException
	 */
	public static function deleteDir($directory)
	{
		if ( ! is_dir($directory))
			throw new InvalidPathException('Cannot delete directory: given path "' . Debug::cleanPath($directory) . '" is not a directory.');

		$items = new \FilesystemIterator($directory);

		foreach ($items as $item)
		{
			// If the item is a directory, we can just recurse into the
			// function and delete that sub-directory, otherwise we'll
			// just deleete the file and keep going!
			if ($item->isDir())
			{
				static::deleteDir($item->getRealPath());
			}
			else
			{
				unlink($item->getRealPath());
			}
		}

		return rmdir($directory);
	}

	/**
	 * Attempt to get the mime type from a file. This method is horribly
	 * unreliable, due to PHP being horribly unreliable when it comes to
	 * determining the mime type of a file.
	 *
	 *     $mime = File::mime($file);
	 *
	 * @param string File name or path
	 * @return string|bool Mime type or false on failure
	 */
	public static function mime($filename)
	{
		// Get the complete path to the file
		$filename = realpath($filename);

		// Get the extension from the filename
		$extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

		if (preg_match('/^(?:jpe?g|png|[gt]if|bmp|swf)$/', $extension))
		{
			// Use getimagesize() to find the mime type on images
			$file = getimagesize($filename);

			if (isset($file['mime']))
				return $file['mime'];
		}

		if (class_exists('\\finfo', false))
		{
			if ($info = new \finfo(defined('FILEINFO_MIME_TYPE') ? FILEINFO_MIME_TYPE : FILEINFO_MIME))
			{
				return $info->file($filename);
			}
		}

		if (ini_get('mime_magic.magicfile') and function_exists('mime_content_type'))
		{
			// The mime_content_type function is only useful with a magic file
			return mime_content_type($filename);
		}

		if ( ! empty($extension))
		{
			return Config::get('mimes.' . $extension);
		}

		// Unable to find the mime-type
		return false;
	}

	/**
	 * Split a file into pieces matching a specific size. Used when you need to
	 * split large files into smaller pieces for easy transmission.
	 *
	 *     $count = File::split($file);
	 *
	 * @param string File to be split
	 * @param string Directory to output to, defaults to the same directory as the file
	 * @param int Size, in MB, for each piece to be
	 * @return int The number of pieces that were created
	 */
	public static function split($filename, $piece_size = 10)
	{
		// Open the input file
		$file = fopen($filename, 'rb');

		// Change the piece size to bytes
		$piece_size = floor($piece_size * 1024 * 1024);

		// Write files in 8k blocks
		$block_size = 1024 * 8;

		// Total number of peices
		$pieces = 0;

		while ( ! feof($file))
		{
			// Create another piece
			$peices += 1;

			// Create a new file piece
			$piece = str_pad($pieces, 3, '0', STR_PAD_LEFT);
			$piece = fopen($filename.'.'.$piece, 'wb+');

			// Number of bytes read
			$read = 0;

			do
			{
				// Transfer the data in blocks
				fwrite($piece, fread($file, $block_size));

				// Another block has been read
				$read += $block_size;
			}
			while ($read < $piece_size);

			// Close the piece
			fclose($piece);
		}

		// Close the file
		fclose($file);

		return $pieces;
	}

	/**
	 * Join a split file into a whole file. Does the reverse of [File::split].
	 *
	 *     $count = File::join($file);
	 *
	 * @param string Split filename, without .000 extension
	 * @param string Output filename, if different then an the filename
	 * @return int The number of pieces that were joined.
	 */
	public static function join($filename)
	{
		// Open the file
		$file = fopen($filename, 'wb+');

		// Read files in 8k blocks
		$block_size = 1024 * 8;

		// Total number of peices
		$pieces = 0;

		while (is_file($piece = $filename.'.'.str_pad($pieces + 1, 3, '0', STR_PAD_LEFT)))
		{
			// Read another piece
			$pieces += 1;

			// Open the piece for reading
			$piece = fopen($piece, 'rb');

			while ( ! feof($piece))
			{
				// Transfer the data in blocks
				fwrite($file, fread($piece, $block_size));
			}

			// Close the peice
			fclose($piece);
		}

		return $pieces;
	}

	/**
	 * Send headers, make the browser download a file and stop the script
	 *
	 * @param string $path Where the file is located
	 * @param string $name If not set, will be the filename of given path
	 * @param string $mime If not set, will be the extension of given filename
	 * @throws InvalidPathException
	 */
	public static function download($path, $name = null, $mime = null)
	{
		$size = static::getSize($path);
		is_null($name) and $name = pathinfo($path, PATHINFO_FILENAME);
		is_null($mime) and $mime = pathinfo($name, PATHINFO_EXTENSION);
		$mime = static::mime($name);

		if( ! is_file($path))
			throw new InvalidPathException('File "' . Debug::cleanPath($path) . '" does not exist.');

		$file = fopen($path, 'rb');
		flock($file, LOCK_SH);

		ini_get('zlib.output_compression') and ini_set('zlib.output_compression', 0);
		! ini_get('safe_mode') and set_time_limit(0);

		header('Content-Type: ' . $mime);
		header('Content-Disposition: attachment; filename="' . $name . '"');
		header('Content-Description: File Transfer');
		header('Content-Length: ' . $size);
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

		while( ! feof($file))
			echo fread($file, 2048);

		flock($file, LOCK_UN);
		fclose($file);

		exit;
	}
}