<?php

namespace Meanbee\Magedbm2\Service;

interface FilesystemInterface
{
    /**
     * Move the given file.
     *
     * @param string $old_file
     * @param string $new_file
     *
     * @return bool true on success, false otherwise.
     */
    public function move($old_file, $new_file);

    /**
     * Delete the given file.
     *
     * @param string $file
     *
     * @return bool true on success, false otherwise.
     */
    public function delete($file);
}
