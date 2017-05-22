<?php

namespace Meanbee\Magedbm2\Service\Filesystem;

use Meanbee\Magedbm2\Service\FilesystemInterface;

class Simple implements FilesystemInterface
{

    /**
     * @inheritdoc
     */
    public function move($old_file, $new_file)
    {
        $dir = dirname($new_file);

        if (file_exists($dir) && !is_dir($dir)) {
            return false;
        }

        if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
            return false;
        }

        return rename($old_file, $new_file);
    }

    /**
     * @inheritdoc
     */
    public function delete($file)
    {
        return (file_exists($file)) ? unlink($file) : false;
    }
}
