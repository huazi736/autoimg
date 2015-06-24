<?php


    /**
     * 配置文件
     */
    
    //原图片地址
    define('SRC_IMAGE', __DIR__ . '/src/');
    
    //缩略图存放地址
    define('THUMB_IMAGE', __DIR__ . '/');
    
    //图片转换工具地址
    define('CONVERT_CMD_PATH', '/usr/local/imagemagick/bin/convert');


    //尺寸限制
    define('SIZE_FILTER', '210x280,300x400,450x600');

    //默认压缩质量
    define('DEFAULT_QUALITY',80);


    //实例化图片处理类
	$img_obj = new image();

	$img_obj->run();

	
    //图片处理类
    class image {
    
        public $convert_cmd_path ;
        public $request_file;
    
        public function __construct() {
        	
            $this->convert_cmd_path = CONVERT_CMD_PATH;

            //请求字串 /file/abc.jpg_300x400x80.jpg
            // LNMP环境下 获取$_SERVER ['REQUEST_URI'];  LANMP下配置成Get参数 
            // LANMP 环境，nginx 配置  
            //  if (!-f $request_filename) {
            //                rewrite ^(.*)$ /autoimg.php?a=$request_uri;
            //                # expires 30d;
            //
            //        }
            
            $this->request_file = trim($_GET['a']);

            if (!$this->request_file) {
            	exit;
            }

            $this->checkFileType();
        }
    
        /**
         * 命令行模式缩放图片
         */
    
        public function resizeImageByCmd($args = array()) {
            $src = $args ['src_file'];
            $dst = $args ['des_file'];
            $dst_w = intval ( $args ['dst_w'] );
            $dst_h = intval ( $args ['dst_h'] );
            $quality = intval ( $args ['quality'] );
    
            //gif动画的图片，缩列图只取第一帧
            if (preg_match("/.+\.gif$/", $src,$m)) {
                $src .= '[0]';
            }
    
            $command = "{$this->convert_cmd_path} -strip -resize '{$dst_w}x{$dst_h}>' -quality {$quality}% {$src} {$dst}";

            @exec($command, $output, $retval);		// $output 返回值 	 $retval 命令状态
    
            if (is_file($dst)) {
                return true;
            } else {
                return false;
            }
            return is_file($dst) ? true : false;
        }

    	/**
    	 * 清除图片附加信息、压缩图片质量
    	 */
        public function tripsImage($args = array()) {
            $src = $args['src_file'];
            $dst = $args['des_file'];
            $quality = $args['quality'];
            $command = "{$this->convert_cmd_path} -strip -quality {$quality}% {$src} {$dst}";
            @exec($command);
            return is_file($dst) ? true : false;
        }
    
        /**
         * 输出图片
         */
        function echoImage($imgsrc) {
            $info=getimagesize($imgsrc);
            header("content-type:image/jpeg;");
            echo fread(fopen($imgsrc,'rb'),filesize($imgsrc));
        }
    
        /**
         * 记录日志文件
         */
        function _log($str) {
            $handler = fopen('log.txt','rb');
            fwrite($handler,$str);
            fwrite($handler,"\n");
            fclose($handler);
        }

        //判断临时目录是否存在，不存在则创建目录
        function createDir($rootPath,$tmp_path) {

        	$rootPath = rtrim($rootPath,'/') . '/';
        	$tmp_path = trim($tmp_path,'/');

        	if (!file_exists($rootPath . $tmp_path)) {
				$tmp_arr = explode('/', $tmp_path);

				$a = rtrim($rootPath,'/');
				foreach ($tmp_arr as $val) {
					$a .= '/' . $val;
					if (!file_exists($a)) {
						mkdir($a,0777);
					}
				}
			}
        }

        /**
         * 判断请求文件是否是JPG类型
         */
        function checkFileType() {
        	//修正访问非JPG文件时，返回
			$file_type = strtolower(substr($this->request_file,strrpos($this->request_file,'.') + 1));
			if ($file_type != 'jpg') {
				exit;
			}
        }

        /**
         * match 图片尺寸
         */
        function pregMatchImageSize() {
        	//匹配请求文件,生成临时文件
        	$args = array();

        	$filename = basename($this->request_file);

        	$path = trim(dirname($this->request_file),'/');

        	$path = $path ? $path . '/' : '';

			if (preg_match ( "/(.+\.(png|jpg|jpeg|gif))_(\d+)x(\d+)x?(\d+)?\.(jpg|png|jpeg|gif)/i", $filename, $m )) {
				
				//源文件名称
				$args['filename'] = $m[1];
				//请求尺寸宽度
				$args['dst_w'] = $m[3];
				//请求尺寸高度
				$args['dst_h'] = $m[4];
				//请求图片压缩质量
				$args['quality'] = empty($m[5]) ? DEFAULT_QUALITY : $m[5];
				//请求源图片
				$args['src_file'] = rtrim(SRC_IMAGE,'/') . '/' . $path . $m[1];
				//目标图片
				$args['des_file'] = rtrim(THUMB_IMAGE,'/') . $this->request_file;
				
			} elseif (preg_match("/.+\.(png|jpg|jpeg|gif)$/", $filename,$m)) {   //没有尺寸请求原图

				$args['filename'] = $m[0];

				$args['quality'] = DEFAULT_QUALITY;

				$args['src_file'] = rtrim(SRC_IMAGE,'/') . '/' . $path . $m[0];

				$args['des_file'] = rtrim(THUMB_IMAGE,'/') . $this->request_file;

			}

			return $args;
        }

        /**
         * 判断请求尺寸是否合法
         */
        function checkImageSize(&$args) {

        	$request_size = '';
        	if (isset($args['dst_w']) && isset($args['dst_h'])) {
        		$request_size = $args['dst_w'] . 'x' . $args['dst_h'];
        	}
        	$size_filter_arr = explode(',', SIZE_FILTER);

        	if ($request_size) {
        		if (in_array($request_size,$size_filter_arr)) {
        			return TRUE;
        		}
        		return FALSE;
        	}
        	return TRUE;
        }

        /**
         * 开始生成缩略图
         */
        function run() {

        	$args = $this->pregMatchImageSize();

        	if (empty($args) || !$this->checkImageSize($args)) {
        		exit;
        	}

        	//判断源文件是否存在
        	if (!file_exists($args['src_file'])) {
        		exit;
        	}

        	$this->createDir(THUMB_IMAGE,dirname($this->request_file));

        	if (isset($args['dst_w']) && isset($args['dst_h'])) {
        		$result = $this->resizeImageByCmd($args);
        	} else {
        		$result = $this->tripsImage($args);
        	}

        	$result ? $this->echoImage($args['des_file']) : '';

        }
	
    }
    
    
    

