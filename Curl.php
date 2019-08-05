<?php

namespace Swolley\Curl;

class Curl
{
	public function get(string $url, array $queryParams = [], string $token = NULL)
	{
		$this->withoutBody('GET', $url, $queryParams, $token);
	}

	public function delete(string $url, array $queryParams = [], string $token = NULL)
	{ 
		$this->withoutBody('DELETE', $url, $queryParams, $token);
	}

	public function post(string $url, array $fields, array $pathNames, string $token = NULL)
	{
		return $this->withBody('POST', $url, $fields, $pathNames, $token);
	}

	public function put(string $url, array $fields, array $pathNames, string $token = NULL)
	{ 
		return $this->withBody('PUT', $url, $fields, $pathNames, $token);
	}

	public function patch(string $url, array $fields, array $pathNames, string $token = NULL)
	{ 
		return $this->withBody('PATCH', $url, $fields, $pathNames, $token);
	}

	/**
	 * makes calls containing body (POST, PUT, PATCH)
	 * @param   string  $url        destination url
	 * @param   array   $fields     textual data
	 * @param   array   $pathNames  file's paths
	 * @param	string	$token		authorization token
	 * @return  mixed               response data or array with error code and message if something went wrong
	 */
	private function withBody(string $method, string $url, array $fields, array $pathNames, string $token = NULL)
	{ 
		$curl = curl_init();
		$boundary = uniqid();
		$delimiter = '-------------' . $boundary;
		$files = $this->loadFileContents($pathNames);
		$post_data = $this->buildDataFiles($boundary, $fields, $files);
		$http_header = ['Content-Type: ' . (count($pathNames) > 0 ? 'multipart/form-data; boundary=' . $delimiter : 'application/json')];
		if ($token) {
			$http_header[] = 'Authorization: Bearer ' . $token;
		}

		curl_setopt_array($curl, [
			CURLOPT_URL => $url,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			//CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => strtoupper($method),
			CURLOPT_POST => 1,
			CURLOPT_POSTFIELDS => $post_data,
			CURLOPT_HTTPHEADER => $http_header
		]);
		$response = curl_exec($curl);
		$info = curl_getinfo($curl);

		$err = curl_error($curl);
		curl_close($curl);
		
		$parsed_response = @json_decode($response, true);
		if(last_json_error() === JSON_ERROR_NONE) {
    		$response = $parsed_response;
		}

		return $info['http_code'] === 200 || ($method === 'POST' && $info['http_code'] === 201) ? $response : [
			'error' => $info['http_code'],
			'message' => $err
		];
	}

	/**
	 * makes calss without body
	 * @param   string  $url        	destination url
	 * @param   array   $queryParams    textual data
	 * @param   array   $pathNames  	file's paths
	 * @param	string	$token			authorization token
	 * @return  mixed               	response data or array with error code and message if something went wrong
	 */
	private function withoutBody(string $method, string $url, array $queryParams = [], string $token = NULL)
	{
		$curl = curl_init();
		$url_data = http_build_query($queryParams);
		$http_header = ['Content-Type: application/json'];
		if ($token) {
			$http_header[] = 'Authorization: Bearer ' . $token;
		}

		curl_setopt_array($curl, [
			CURLOPT_URL => $url . "?" . $url_data,
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_MAXREDIRS => 10,
			CURLOPT_TIMEOUT => 30,
			//CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
			CURLOPT_CUSTOMREQUEST => strtoupper($method),
			CURLOPT_HTTPHEADER => $http_header
		]);
		$response = curl_exec($curl);
		$info = curl_getinfo($curl);

		$err = curl_error($curl);
		curl_close($curl);
		
		$parsed_response = @json_decode($response, true);
		if(last_json_error() === JSON_ERROR_NONE) {
    		$response = $parsed_response;
		}

		return $info['http_code'] === 200 ? $response : [
			'error' => $info['http_code'],
			'message' => $err
		];
	}

	/**
	 * returns content of passed files
	 * @param   array    $pathNames     array of paths
	 * @return  array    $files          array of contents
	 */
	private function loadFileContents(array &$pathNames)
	{
		$files = [];
		foreach ($pathNames as $path) {
			$splitted_path = explode('/', $path);
			$file_name = array_pop($splitted_path);
			$files[$file_name] = file_get_contents($path);
		}

		return $files;
	}

	/**
	 * prepares data to be posted in multipart/form-data style
	 * @param   string  $boundary   call identifier
	 * @param   array   $fields     textual data
	 * @param   array   $files      file content's array
	 * @return  string  $data       formed post data string
	 */
	private function buildDataFiles(string &$boundary, array &$fields, array &$files): string
	{
		$data = '';
		$eol = "\r\n";
		$delimiter = '-------------' . $boundary;

		if (count($files)) {
			foreach ($fields as $name => $content) {
				$data .= "--" . $delimiter . $eol
					. 'Content-Disposition: form-data; name="' . $name . "\"" . $eol . $eol
					. $content . $eol;
			}

			foreach ($files as $name => $content) {
				$data .= "--" . $delimiter . $eol
					. 'Content-Disposition: form-data; name="' . $name . '"; filename="' . $name . '"' . $eol
					. 'Content-Transfer-Encoding: binary' . $eol;
				$data .= $eol;
				$data .= $content . $eol;
				$data .= "--" . $delimiter . "--" . $eol;
			}
		} else {
			$data = json_encode($fields, JSON_NUMERIC_CHECK | JSON_PRETTY_PRINT);
		}

		return $data;
	}
}
