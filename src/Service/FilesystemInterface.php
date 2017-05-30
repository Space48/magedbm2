<?php

namespace Meanbee\Magedbm2\Service;

interface FilesystemInterface
{
    /**
     * Write the given contents to a file.
     *
     * @param string $file
     * @param string $data
     *
     * @return bool true on success, false otherwise.
     */
    public function write($file, $data);

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
