<?php

class LocalAssets
{
    private static function rootToPath($root){
      $kirby = kirby();
      $mediaRoot = $kirby->root('media');

      $path = 'media' . str_replace($mediaRoot, '', $root);
      return $path;
    }

    public static function getMediaRoot(){
      $kirby = kirby();
      $mediaRoot = $kirby->root('media') . '/local-assets';
      if(!is_dir($mediaRoot)){
        Dir::make($mediaRoot);
      }
      return $mediaRoot;
    }

    public static function getDownloadsRoot(){
      $mediaRoot = self::getMediaRoot();
      $downloadsRoot = $mediaRoot . '/downloads';
      if(!is_dir($downloadsRoot)){
        Dir::make($downloadsRoot);
      }
      return $downloadsRoot;
    }

    public static function newDownloadPath(){
      $downloadsRoot = self::getDownloadsRoot();
      $downloadPath = $downloadsRoot . '/' . uniqid();
      return $downloadPath;
    }

    public static function fileDirPath($url){
      $url_sha1 = sha1($url);
      $mediaRoot = self::getMediaRoot();

      $fileDir = $mediaRoot . '/' . $url_sha1;

      return $fileDir;
    }

    public static function filePath($url, $ext){
      $url_sha1 = sha1($url);
      $fileDir = self::fileDirPath($url);

      $filePath = $fileDir . '/' . $url_sha1;
      if(!empty($ext)){
        $filePath .= '.' . $ext;
      }

      return $filePath;
    }
    
    public static function findFileDir($url){
      $fileDir = self::fileDirPath($url);
      if(is_dir($fileDir)) return $fileDir;
      return null;
    }

    public static function createFileDir($url){
      $fileDir = self::fileDirPath($url);
      if(!is_dir($fileDir)){
        Dir::make($fileDir);
      }

      return $fileDir;
    }

    public static function findAsset($url){
      $fileDir = self::findFileDir($url);
      if(empty($fileDir)) return null;

      $files = Dir::files($fileDir);
      foreach($files as $file){
        if(substr($file, 0, 1) == '.') continue;
        $fileRoot = $fileDir . '/' . $file;
        $filePath = self::rootToPath($fileRoot);
        $asset = new Asset($filePath);
        return $asset;
      }
    }

    public static function downloadFile($url){
      $downloadPath = self::newDownloadPath();
      try{
        file_put_contents($downloadPath, fopen($url, 'r'));
        $ext = 'jpg';
        $targetPath = self::filePath($url, $ext);
  
        F::move($downloadPath, $targetPath);
  
        return $targetPath;
      } catch(Exception $e){
        if(F::exists($downloadPath)){
          F::remove($downloadPath);
        }
        return null;
      }
    }
    
    public static function createAsset($url){
      $fileDir = self::createFileDir($url);

      $fileRoot = self::downloadFile($url);

      if(!empty($fileRoot)){
        $filePath = self::rootToPath($fileRoot);
        $asset = new Asset($filePath);
        return $asset;
      }
      return null;
    }

    public static function getLocalAsset(string $url)
    {
      $asset = self::findAsset($url);
      if(!empty($asset)) return $asset;

      $asset = self::createAsset($url);
      return $asset;
    }
}
