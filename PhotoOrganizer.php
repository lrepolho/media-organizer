<?php

class PhotoOrganizer
{
    private $dryRun = true;

    public function __construct()
    {
        @$photosDir = $_SERVER['argv']['1'];
        @$destinationDir = $_SERVER['argv']['2'];

        if (empty($photosDir) || empty($destinationDir)) {
            throw new \Exception("Mandatory arguments not set");
        }

        $files = scandir($photosDir);
        foreach ($files as $file) {
            if ($file == '.' || $file == '..') {
                continue;
            }
            $originalFilePath = "$photosDir$file";
            if (is_dir($originalFilePath)) {
                continue;
            }
            $originalFileExtension = strtolower(pathinfo($originalFilePath, PATHINFO_EXTENSION));

            $subDir = $this->getSubDir(
                $this->isPicture($originalFileExtension),
                $originalFilePath
            );

            $newDir = "$destinationDir$subDir";
            $file = $this->getFileNameAsSha($photosDir, $file);
            $newFile = $newDir . "/$file";
            echo "Moving $originalFilePath to $newFile" . PHP_EOL;

            if (false === $this->dryRun) {
                @mkdir($newDir);
                rename($originalFilePath, $newFile);
            }
        }
    }

    private function getSubDir(bool $isPicture, string $originalFilePath)
    {
        $subDir = 'other';

        if ($isPicture) {
            $photoDate = $this->getPhotoDate($originalFilePath);
            $subDir = $photoDate[0] . $photoDate[1];
        }

        return $subDir;
    }

    private function getFileNameAsSha(string $photosDir, string $file)
    {
        $oldFile = "$photosDir$file";
        $extension = strtolower(pathinfo($oldFile, PATHINFO_EXTENSION));
        $hash = hash_file('sha256', $oldFile);
        $newFile = "$hash.$extension";
        return $newFile;
    }

    public function scanDirectoryRecursively(string $folderPath)
    {
        $result = array();

        $cdir = scandir($folderPath);
        foreach ($cdir as $key => $value) {
            if (!in_array($value, array(".",".."))) {
                if (is_dir($folderPath . DIRECTORY_SEPARATOR . $value)) {
                    $result[$folderPath . '/' . $value] = $this->scanDirectoryRecursively($folderPath . DIRECTORY_SEPARATOR . $value);
                } else {
                    $result[] = $value;
                }
            }
        }

        return $result;
    }

    public function isPicture(string $fileExtension)
    {
        $fileExtension = strtolower($fileExtension);

        if ($fileExtension == 'jpg' || $fileExtension == 'jpeg' || $fileExtension == 'nef') {
            return true;
        }

        return false;
    }

    public function getPhotoDate(string $filePath)
    {
        $exifData = exif_read_data($filePath);

        if (!isset($exifData['DateTimeOriginal'])) {
            return explode('-', date(DATE_ATOM, $exifData['FileDateTime']));
        }

        return explode(':', $exifData['DateTimeOriginal']);
    }
}

new PhotoOrganizer();
