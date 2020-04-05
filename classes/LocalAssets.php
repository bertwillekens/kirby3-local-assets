<?php

class LocalAssets
{
    private static function rootToPath($root){
      $kirby = kirby();
      $mediaRoot = $kirby->root('media');

      $path = 'media' . str_replace($mediaRoot, '', $root);
      return $path;
    }

    private static function getMediaRoot(){
      $kirby = kirby();
      $mediaRoot = $kirby->root('media') . '/local-assets';
      if(!is_dir($mediaRoot)){
        Dir::make($mediaRoot);
      }
      return $mediaRoot;
    }

    private static function getDownloadsRoot(){
      $mediaRoot = self::getMediaRoot();
      $downloadsRoot = $mediaRoot . '/downloads';
      if(!is_dir($downloadsRoot)){
        Dir::make($downloadsRoot);
      }
      return $downloadsRoot;
    }

    private static function newDownloadPath(){
      $downloadsRoot = self::getDownloadsRoot();
      $downloadPath = $downloadsRoot . '/' . uniqid();
      return $downloadPath;
    }

    private static function fileDirPath($uid){
      $mediaRoot = self::getMediaRoot();

      $fileDir = $mediaRoot . '/' . $uid;

      return $fileDir;
    }

    private static function filePath($uid, $ext){
      $fileDir = self::fileDirPath($uid);

      $filePath = $fileDir . '/' . $uid;
      if(!empty($ext)){
        $filePath .= '.' . $ext;
      }

      return $filePath;
    }
    
    private static function findFileDir($uid){
      $fileDir = self::fileDirPath($uid);
      if(is_dir($fileDir)) return $fileDir;
      return null;
    }

    private static function createFileDir($uid){
      $fileDir = self::fileDirPath($uid);
      if(!is_dir($fileDir)){
        Dir::make($fileDir);
      }

      return $fileDir;
    }

    private static function removeFileDir($uid){
      $fileDir = self::fileDirPath($uid);
      if(is_dir($fileDir)){
        Dir::make($fileDir);
      }

      return $fileDir;
    }

    private static function findAsset($uid){
      $fileDir = self::findFileDir($uid);
      if(empty($fileDir)) return null;

      $files = Dir::files($fileDir);
      foreach($files as $file){
        if(substr($file, 0, 1) == '.') continue;
        if($file == 'url.txt') continue;
        $fileRoot = $fileDir . '/' . $file;
        $filePath = self::rootToPath($fileRoot);
        $asset = new Asset($filePath);
        return $asset;
      }
    }

    private static function downloadFile($url, $uid){
      $downloadPath = self::newDownloadPath();
      $urlFile = '';

      try{
        file_put_contents($downloadPath, fopen($url, 'r'));
        $ext = 'jpg';
        $targetPath = self::filePath($uid, $ext);
  
        F::move($downloadPath, $targetPath);

        $fileDir = self::fileDirPath($uid);
        $urlFile =  $fileDir . '/url.txt';
        file_put_contents($urlFile, $uid);
  
        return $targetPath;
      } catch(Exception $e){
        if(F::exists($downloadPath)){
          F::remove($downloadPath);
        }
        if(!empty($urlFile)){
          F::remove($urlFile);
        }
        throw $e;
      }
    }
    
    private static function createAsset($url, $uid){
      $fileDir = self::createFileDir($uid);

      try{
        $fileRoot = self::downloadFile($url, $uid);
        $filePath = self::rootToPath($fileRoot);
        $asset = new Asset($filePath);
        return $asset;
      } catch(Exception $e){
        if(is_dir($fileDir)){
          Dir::remove($fileDir);
        }
        return null;
      }
    }

    public static function getLocalAsset(string $url, ?string $uid)
    {
      if(empty($uid)) {
        $uid = sha1($url);
      }

      $asset = self::findAsset($uid);
      if(!empty($asset)) return $asset;

      $asset = self::createAsset($url, $uid);
      return $asset;
    }
}
