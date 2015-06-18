<?php

class image {
	
	public $convert_cmd_path ;
	
	public function __construct() {
		$this->convert_cmd_path = CONVERT_CMD_PATH;
	}
	
	/**
	 * ������ģʽ����ͼƬ
	 */
	
	public function resize_image_cmd($args = array()) {
		$src = $args ['src'];
		$dst = $args ['dst'];
		$dst_w = intval ( $args ['dst_w'] );
		$dst_h = intval ( $args ['dst_y'] );
		$quality = intval ( $args ['quality'] );
		
		//gif������ͼƬ������ͼֻȡ��һ֡
		if (preg_match("/.+\.gif$/", $src,$m)) {
			$src .= '[0]';
		}
		
		$command = "{$this->convert_cmd_path} -strip -resize '{$dst_w}x{$dst_h}>' -quality {$quality}% {$src} {$dst}";
		
		@exec($command, $output, $retval);		// $output ����ֵ 	 $retval ����״̬
		
		if (is_file($dst)) {
			return true;
		} else {
			$this->_log($command);
			return false;
		}
		return is_file($dst) ? true : false;
		
	} 
	
	public function trips_image($args = array()) {
		$src = $args['src'];
		$dst = $args['dst'];
		$quality = $args['quality'];
		$command = "{$this->convert_cmd_path} -strip -quality {$quality}% {$src} {$dst}";
		@exec($command);
		return is_file($dst) ? true : false;
	}
	
	/**
	 * ���ͼƬ
	 */
	function echo_image($imgsrc) {
		$info=getimagesize($imgsrc); 
		header("content-type:{$info['mime']}");
		echo fread(fopen($imgsrc,'rb'),filesize($imgsrc));
	} 
	
	/**
	 * ��¼��־�ļ�
	 */
	function _log($str) {
		$handler = fopen('log.txt','rb');
		fwrite($handler,$str);
		fwrite($handler,"\n");
		fclose($handler);
	}
	 
}