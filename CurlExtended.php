<?php
namespace Swolley\Curl;

final class CurlExtended extends Curl {
    /** 
     * post alias with file deletion
     * @param   string  $url                destination url
     * @param   array   $fields             body object
     * @param   array   $filepaths          array of files' path
     * @param	string	$token				authorization token
     * @param   bool    $deleteAfterPost    deletes local files if post ended correctly
     * @return  mixed   $response           post response
     * @see             $this->post
     * */
	public function postMulti(string $url, array $fields, array $filepaths, string $token = NULL, bool $deleteAfterPost = FALSE){
		$response = $this->post($url, $fields, $filepaths, $token);
		if($response === TRUE && $deleteAfterPost) {
			$this->deleteLocalFiles($filepaths);
		}
		
		return $response;
	}
    
    /** 
     * post alias with no fields and file deletion
     * @param   string  $url                destination url
     * @param   array   $filepaths          array of files' path
     * @param	string	$token				authorization token
     * @param   bool    $deleteAfterPost    deletes local files if post ended correctly
     * @return  mixed   $response           post response
     * @see             $this->post
     * */
	public function postFiles(string $url, array $filepaths, string $token = NULL, bool $deleteAfterPost = FALSE){
		$response = $this->post($url, [], $filepaths, $token);
		if($response === TRUE && $deleteAfterPost) {
			$this->deleteLocalFiles($filepaths);
		}
		
		return $response;
	}
    
    /** 
     * post alias without files
     * @param   string  $url                destination url
     * @param   array   $fields             body object
     * @param	string	$token				authorization token
     * @return  mixed   $response           post response
     * @see             $this->post
     * */
	public function postData(string $url, array $fields, string $token = NULL){
		return $this->post($url, $fields, [], $token);
	}

    /**
	 * deletes local files. called after post finishes
	 * @param	array	$file	array of paths
	 */
	protected function deleteLocalFiles(array $files) {
		foreach($files as $file) {
			unlink($file);
		}
	}
}
