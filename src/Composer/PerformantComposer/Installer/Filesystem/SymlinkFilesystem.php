<?php

namespace Composer\PerformantComposer\Installer\Filesystem;

use Composer\Util\Filesystem;

class SymlinkFilesystem extends Filesystem
{
    /**
      * Create a symlink
      *
      * @param string $sourcePath
      * @param string $symlinkPath
      *
      * @return bool
      */
    public function ensureSymlinkExists($sourcePath, $symlinkPath)
    {
        if (!is_link($symlinkPath)) {
            $this->ensureDirectoryExists(dirname($symlinkPath));


            return $this->symlink($sourcePath, $symlinkPath);
        }

        return false;
    }


    /**
     * @param string $symlinkPath
     *
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function removeSymlink($symlinkPath)
    {
        if (is_link($symlinkPath)) {
            if (!$this->unlink($symlinkPath)) {
                // @codeCoverageIgnoreStart
                throw new \RuntimeException('Unable to remove the symlink : ' . $symlinkPath);
                // @codeCoverageIgnoreEnd
            }

            return true;
        }

        return false;
    }

    public function symlink($target, $link)
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            return exec('junction ' . escapeshellarg($link) . ' ' . escapeshellarg($target));
        } else {
            if (is_dir($link)) {
                $this->delTree($link);
            }

            return symlink($target, $link);
        }
    }

    public function unlink($link)
    {
        if (defined('PHP_WINDOWS_VERSION_BUILD')) {
            return exec('junction -d ' . escapeshellarg($link));
        } else {
            return unlink($link);
        }
    }

    /**
     * @param string $directoryPath
     *
     * @return bool
     *
     * @throws \RuntimeException
     */
    public function removeEmptyDirectory($directoryPath)
    {
        if (is_dir($directoryPath) && $this->isDirEmpty($directoryPath)) {
            if (!$this->removeDirectory($directoryPath)) {
                // @codeCoverageIgnoreStart
                throw new \RuntimeException('Unable to remove the directory : ' . $directoryPath);
                // @codeCoverageIgnoreEnd
            }

            return true;
        }

        return false;
    }

    public function delTree($dir)
    {
        $files = array_diff(scandir($dir), array('.','..'));
        foreach ($files as $file) {
            (is_dir("$dir/$file")) ? $this->delTree("$dir/$file") : unlink("$dir/$file");
        }
        return rmdir($dir);
    }
}
