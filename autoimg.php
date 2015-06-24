<?php


    /**
     * �����ļ�
     */
    
    //ԭͼƬ��ַ
    define('SRC_IMAGE', __DIR__ . '/src/');
    
    //����ͼ��ŵ�ַ
    define('THUMB_IMAGE', __DIR__ . '/');
    
    //ͼƬת�����ߵ�ַ
    define('CONVERT_CMD_PATH', '/usr/local/imagemagick/bin/convert');


    //�ߴ�����
    define('SIZE_FILTER', '210x280,300x400,450x600');

    //Ĭ��ѹ������
    define('DEFAULT_QUALITY',80);


    //ʵ����ͼƬ������
	$img_obj = new image();

	$img_obj->run();

	
    //ͼƬ������
    class image {
    
        public $convert_cmd_path ;
        public $request_file;
    
        public function __construct() {
        	
            $this->convert_cmd_path = CONVERT_CMD_PATH;

            //�����ִ� /file/abc.jpg_300x400x80.jpg
            // LNMP������ ��ȡ$_SERVER ['REQUEST_URI'];  LANMP�����ó�Get���� 
            // LANMP ������nginx ����  
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
         * ������ģʽ����ͼƬ
         */
    
        public function resizeImageByCmd($args = array()) {
            $src = $args ['src_file'];
            $dst = $args ['des_file'];
            $dst_w = intval ( $args ['dst_w'] );
            $dst_h = intval ( $args ['dst_h'] );
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
                return false;
            }
            return is_file($dst) ? true : false;
        }

    	/**
    	 * ���ͼƬ������Ϣ��ѹ��ͼƬ����
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
         * ���ͼƬ
         */
        function echoImage($imgsrc) {
            $info=getimagesize($imgsrc);
            header("content-type:image/jpeg;");
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

        //�ж���ʱĿ¼�Ƿ���ڣ��������򴴽�Ŀ¼
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
         * �ж������ļ��Ƿ���JPG����
         */
        function checkFileType() {
        	//�������ʷ�JPG�ļ�ʱ������
			$file_type = strtolower(substr($this->request_file,strrpos($this->request_file,'.') + 1));
			if ($file_type != 'jpg') {
				exit;
			}
        }

        /**
         * match ͼƬ�ߴ�
         */
        function pregMatchImageSize() {
        	//ƥ�������ļ�,������ʱ�ļ�
        	$args = array();

        	$filename = basename($this->request_file);

        	$path = trim(dirname($this->request_file),'/');

        	$path = $path ? $path . '/' : '';

			if (preg_match ( "/(.+\.(png|jpg|jpeg|gif))_(\d+)x(\d+)x?(\d+)?\.(jpg|png|jpeg|gif)/i", $filename, $m )) {
				
				//Դ�ļ�����
				$args['filename'] = $m[1];
				//����ߴ���
				$args['dst_w'] = $m[3];
				//����ߴ�߶�
				$args['dst_h'] = $m[4];
				//����ͼƬѹ������
				$args['quality'] = empty($m[5]) ? DEFAULT_QUALITY : $m[5];
				//����ԴͼƬ
				$args['src_file'] = rtrim(SRC_IMAGE,'/') . '/' . $path . $m[1];
				//Ŀ��ͼƬ
				$args['des_file'] = rtrim(THUMB_IMAGE,'/') . $this->request_file;
				
			} elseif (preg_match("/.+\.(png|jpg|jpeg|gif)$/", $filename,$m)) {   //û�гߴ�����ԭͼ

				$args['filename'] = $m[0];

				$args['quality'] = DEFAULT_QUALITY;

				$args['src_file'] = rtrim(SRC_IMAGE,'/') . '/' . $path . $m[0];

				$args['des_file'] = rtrim(THUMB_IMAGE,'/') . $this->request_file;

			}

			return $args;
        }

        /**
         * �ж�����ߴ��Ƿ�Ϸ�
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
         * ��ʼ��������ͼ
         */
        function run() {

        	$args = $this->pregMatchImageSize();

        	if (empty($args) || !$this->checkImageSize($args)) {
        		exit;
        	}

        	//�ж�Դ�ļ��Ƿ����
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
    
    
    

