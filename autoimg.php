<?php

	require 'config.php';
	require 'lib_image.php';
	
	$img_obj = new image();
	
	$default_image = '';
	$thumb_path = THUMB_IMAGE;
	//a参数是lanmp环境，nginx转发给apache时，url转发错误，所以设置参数a
	$file = ltrim($_GET['a'],'/');	//$_SERVER ['REQUEST_URI'];//请求字串 /file/abc.jpg.w320.jpg
	//下面的方式是lnmp环境
	//$file = $_SERVER ['REQUEST_URI'];//请求字串 /file/abc.jpg.w320.jpg
	
	$desfile = SRC_IMAGE . $file; //$_SERVER ['DOCUMENT_ROOT'] . $file; //目标目标路径 /var/www/http/file/abc.jpg.w320.jpg
	
	$tmp_path = dirname($file);
	
	//判断临时文件是否存在
	if (file_exists($thumb_path . $file)) {
		$img_obj->echo_image($thumb_path . $file);
		exit;
	}
	
	//判断临时目录是否存在，不存在则创建目录
	if (!file_exists($thumb_path . $tmp_path)) {
		$tmp_arr = explode('/', $tmp_path);
		$a = rtrim($thumb_path,'/');
		foreach ($tmp_arr as $val) {
			$a .= '/' . $val;
			if (!file_exists($a)) {
				mkdir($a,0777);
			} 
		}
	}
	
	///car/1.jpg_500x300.jpg   /car/19/1.jpg_500x300.jpg  /data/images/car/19/1.jpg_500x300.jpg
	
		
	
	if (!file_exists($desfile)) {
// 		$img_obj->echo_image($default_image);
// 		exit;
	}
	
	$filename = basename($file);
	
	$tmp_file = $thumb_path . $file;
	
	//匹配请求文件,生成临时文件
	if (preg_match ( "/(.+\.(png|jpg|jpeg|gif))_(\d+)x(\d+)x?(\d+)?\.(jpg|png|jpeg|gif)/i", $filename, $m )) {
		$srcfile = dirname($desfile) . '/' . $m [1];
		$width = $m [3];					//匹配出输出文件宽度
		$height = $m[4];
		$quality = empty($m[5]) ? 100 : $m[5];
		
		if (file_exists ( $srcfile )) {	//而且文件不存在
			$args['src'] = $srcfile;
			$args['dst'] = $tmp_file;
			$args['dst_w'] = $width;
			$args['dst_y'] = $height;
			$args['quality'] = $quality;
			if ($img_obj->resize_image_cmd($args)) {
				$img_obj->echo_image($tmp_file);
			} else {
				echo '图片处理失败!';
			}
		}
	}elseif (preg_match("/.+\.(png|jpg|jpeg|gif)$/", $filename,$m)) {
		$srcfile = dirname($desfile) . '/' . $m[0];
		
		if (file_exists($srcfile)) {
			$args['src'] = $srcfile;
			$args['dst'] = $tmp_file;
			$args['quality'] = 100;
			if ($img_obj->trips_image($args)) {
				$img_obj->echo_image($tmp_file);
			} else {
				echo '图片处理失败';
			}
		}
	} elseif(file_exists($srcfile)) {
		$img_obj->echo_image($srcfile);
	}else {
		$img_obj->echo_image($default_image);
		exit;
	}
	
