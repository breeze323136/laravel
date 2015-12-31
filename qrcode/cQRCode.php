<?php
namespace App\Libraries;
/**
 * This class generate QR Codes ISO/IEC 18004 second edition 2006-09-01
 * Not implemented: 
 * Kanji,Mixing Structured Append, and FNC1 modes, Micro QR
 * @version   1.0
 * @author    Omar Eduardo Ortiz Garza <oortiz@tbanc.com>
 * @copyright (c) 2012-2013 Omar Eduardo Ortiz Garza
 * @since     Friday, November 30, 2012
 **/
/**
 *
 *  Este programa es software libre; puedes redistribuir y/o modificar
 *  bajo los terminos de la GNU General Public License como se publico por
 *  la Free Software Foundation; version 2 de la Licencia, o cualquier
 *  (a tu eleccion) version posterior.
 *
 **/
 /**
 * Constans
 **/
 
 //Error Correcction Level
 define("ECL_L",1); define("L",1);
 define("ECL_M",0); define("M",0);
 define("ECL_Q",3); define("Q",3);
 define("ECL_H",2); define("H",2);
 //Encode Mode
 define("EM_NUM",1);
 define("EM_ALP",2);
 define("EM_BIN",4);
 define("EM_JAP",8);
 define("DATA_BITS",0); //used in capacity table
 //Max version
 define ("MAX_VER",40);
 //codeword bits
 define ("CW_BITS",8);
 //Error Correction table
 define ("EC_CW",0);
 define ("BLOCK1",1);
 define ("BLOCK2",2);
 define ("CW_BLCK1",3);
 define ("CW_BLCK2",4);
 //matrix values used to reserve space
 define ("PDP_W", 2); //position detection pattern black
 define ("PDP_B", 3); //position detection pattern white
 define ("TP_W", 4); //timing pattern black
 define ("TP_B", 5); //timing pattern white
 define ("PAP_W", 6); //position adjustment pattern black
 define ("PAP_B", 7); //position adjustment pattern white
 define ("INFO_W", 8); //information and version bits white
 define ("INFO_B", 9); //information and version bits black
 //direction used to fill matrix
 define ("UP",-1);
 define ("LEFT",-1);
 define ("DOWN",1);
 define ("RIGHT",1);
 define ("COL",0);
 define ("ROW",1);
 
//image
define ("PXS",4); //pixel size
define ("QZ", 2); //quiet zone 
define ("PNG",0); 
define ("GIF",1); 
define ("JPG",2); 
define ("FILE_PATH","/tmp/");//relative path to current path
//debug mode show step by step how to create a QR code
define ("DEBUG",0);  // 0 Not debug information       
define ("EQR_ANALYSIS",1);
define ("EQR_ENCODE",2);
define ("EQR_EC",4);
define ("EQR_MATRIX",8);
define ("EQR_MASKING",16);
define ("EQR_FORMAT",32);
define ("EQR_BENCHMARK",64);
define ("EQR_ALL",EQR_ANALYSIS+EQR_ENCODE+EQR_EC+EQR_MATRIX+EQR_MASKING+EQR_FORMAT+EQR_BENCHMARK);
//DEFINE ("DEBUG_LEVEL",EQR_ALL);         
//example just debug ANALYSIS and ENCODE
define ("DEBUG_LEVEL",EQR_BENCHMARK);         
define ("DSCP",0);
define ("TIME",1); 

/**
 * $encode  0x01 (0001) Numeric Mode EM_NUM
 *          0x02 (0010) Alphanumeric Mode EM_ALP
 *          0x04 (0100) Binary Mode EM_BIN
 *          0x08 (1000) Japanese Mode EM_JAP
 * $string string to encode
 * $length  $string length
 * $version    1-40 (21x21 - 177x177)
 * $errorLevel L 7%,M 15%,Q 24%,H 30%
 * $bitsChar (Number of bits in Character Count Indicator) 
 * $strEncoded (string encoded)
 * $dataBits Number of data bits in the encoded string
 * $remBits  remainder Bits
 * $ecCw    ecorrection codewords
 * $genPoly generated polynomial
 * $initExp1 initial exponent block 1
 * $initExp2 initial exponent block 2
 * $ecBlck1 error correction block 1
 * $ecBlck2 error correction block 2
 * $finalSeq final sequence to fill the matrix
 * $dataMatrix matrix filled
 * $matrixLen number of rows and columns in matrix
 * $mask selected mask
 * $info 15 bits of information format
 * $verInfo 18 bits of version information
 * $debug debug flag
 * $format image format
 **/  
class cQRCode {
	private $QRImg;
    private $encode     = EM_NUM;
    private $string     = "";
    private $length     = 0;
    private $version    = 0;
    private $errorLevel = ECL_L;
    private $bitsChar   = 0;
    private $strEncoded = "";
    private $datBits    = 0;
    private $remBits    = 0;
    private $ecCw       = 0;
    private $genPoly    = 0;
    private $initExp1   = 0;
    private $initExp2   = 0;
    private $ecBlck1    = 0;
    private $ecBlck2    = 0;
    private $finalSeq   = "";
    private $dataMatrix =  array();
    private $matrixLen  = 0;
    private $mask       = 0;
    private $info       = "";
    private $verInfo    = "";
    private $debug      = "";
    private $format     = PNG;
    private $error;

    public function __construct($string, $errorLevel = ECL_L){
        if(DEBUG && (DEBUG_LEVEL & EQR_BENCHMARK)){
            $debug       = true;
            $this->debug = new cDebugTime();
        }else{
            $debug = false;
        }
        //select erro level
        switch ($errorLevel){
            case ECL_L:
            case ECL_M:    
            case ECL_Q:
            case ECL_H:
                $this->errorLevel = $errorLevel;
                break;
            case "l":
            case "L": 
                $this->errorLevel = ECL_L;
                break;
            case "m":
            case "M": 
                $this->errorLevel = ECL_M;
                break;
            case "q":
            case "Q": 
                $this->errorLevel = ECL_Q;
                break;
            case "h":
            case "H": 
                $this->errorLevel = ECL_H;
                break;
            default:
                $this->errorLevel = ECL_L;
                break;
        }
        $this->string = $string;
        $this->analysis();
        if($debug){
            $this->debug->setMark("After Analysis");
        }
        $this->encodeStr();
        if($debug){
            $this->debug->setMark("After Encode");
        }
        $this->errorCorrection();
        if($debug){
            $this->debug->setMark("After Error Correction");
        }
        $this->matrix();
        if($debug){
            $this->debug->setMark("After Generate Matrix");
        }
        $this->masking();
        if($debug){
            $this->debug->setMark("After Masking");
            print $this->debug;
        }
    }
    
    //create image
    public function getQRImg($rounded = false) {
    	$gd = imagecreatetruecolor($this->matrixLen + QZ * 2, $this->matrixLen + QZ * 2);
    	$white = imagecolorallocate($gd, 255, 255, 255);
    	imagefill($gd, 0, 0, $white);
    	$black = imagecolorallocate($gd, 0, 0, 0);
    	
    	for($row = 0; $row < $this->matrixLen; $row++){
    		for($col = 0; $col < $this->matrixLen; $col++){
    			if($this->dataMatrix[$row][$col]){
    				imagesetpixel($gd , $col + QZ  , $row + QZ,  $black);
    			}
    		}
    	}
    	
    	$finalLen = ($this->matrixLen + QZ * 2) * PXS;
    	$finalGd  = imagecreate($finalLen, $finalLen);
    	imagecopyresized($finalGd, $gd, 0, 0, 0, 0, $finalLen, $finalLen,
    	$this->matrixLen + QZ * 2, $this->matrixLen + QZ * 2);
    	imagedestroy($gd);
    	/*experimental option
    	 *circular image
    	*/
    	if ($rounded) {
    		// create masking
    		$mask = imagecreatetruecolor($finalLen, $finalLen);
    		imagefill($mask, 0, 0, $white);
    		$transparent = imagecolorallocate($mask, 255, 0, 0);
    		imagecolortransparent($mask, $transparent);
    		imagefilledellipse($mask, $finalLen/2, $finalLen/2, $finalLen - QZ * 2 , $finalLen - QZ * 2, $transparent);
    		$red = imagecolorallocate($mask, 255, 0, 0);
    		imagecopymerge($finalGd, $mask, 0, 0, 0, 0, $finalLen, $finalLen,100);
    		imagecolortransparent($finalGd, $red);
    		imagefill($finalGd,0,0, $red);
    		imagedestroy($mask);
    	}
    	
    	$this->QRImg = $finalGd;
    	
    	return $this;
    }
    
    /**
     * 将图像保存到文件
     * @param string $path 文件保存的绝对路径
     * @param number $quality 图像质量
     * @return 
     */
    public function save($path, $quality = 100, $mkdir = true) {
    	return $this->_output($path, null, $quality, $mkdir);
    }
    
    /**
     * 将图片输出到浏览器
     * @param string $type 输出格式
     * @param number $quality 图像质量
     * @return 
     */
    public function output($type = 'png', $quality = 100) {
    	return $this->_output('stream', $type, $quality);
    }
    
    /**
     * 图像输出处理
     * @param string $path
     * @param string $format_type
     * @param number $quality
     * @return 
     */
    protected function _output($path, $format_type = null, $quality = 75, $mkdir = true) {
    	$write_file = false;
    
    	// 输出到文件
    	if ($path != 'stream') {
    		$save_dir = dirname($path);
    		if (!is_dir($save_dir)) {
    			if (!$mkdir) {
    				$this->error = "指定的路径不可用: $path";
    			}
    
    			mkdir($save_dir, 0755, true);
    		}
    			
    		// 获取输出的图像类型(文件扩展名)
    		$format_type = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    		$write_file = true;
    	}
    
    	switch ($format_type) {
    		case 'jpg':
    			$format_type = "jpeg";
    			break;
    		case 'jpeg':
    			break;
    		case 'gif':
    			$quality = null;
    			break;
    		case 'png':
    			// PNG 的 Alpha 校正
    			$this->_pngAlpha($format_type);
    
    			// PNG 的图像质量转换(取值范围 0 ~ 9)
    			$quality = $quality ? floor($quality / 10) : 9;
    			$quality = $quality > 9 ? 9 : $quality;
    			break;
    		default:
    			$src_img = null;
    			$format_type = "jpeg";
    			break;
    	}
    
    	// 图像输出函数
    	$write_function = "image" . $format_type;
    
    	// 如果生成文件
    	if ($write_file) {
    		$write_function($this->QRImg, $path, $quality);
    		$this->info['path'] = $path;
    	} else {
    		if (!headers_sent()) {
    			header("Content-type:image/" . $format_type);
    		}
    
    		$write_function($this->QRImg, null, $quality);
    	}
    
    	return $this;
    }
    
    /**
     * PNG  图像的 alpha 校正
     * @param string $format
     */
    protected function _pngAlpha($format) {
    	//PNG图像要保持alpha通道
    	if ($format == 'png') {
    		imagealphablending($this->QRImg, false);
    		imagesavealpha($this->QRImg, true);
    	}
    }
    
    // capacity table
    public static function getCapacity($version,$errorLevel,$encode){
        //Version 1
        $i = 1;
        $capacity[$i][ECL_L][EM_NUM] = 41;
        $capacity[$i][ECL_L][EM_ALP] = 25;
        $capacity[$i][ECL_L][EM_BIN] = 17;
        $capacity[$i][ECL_L][EM_JAP] = 10;
        $capacity[$i][ECL_M][EM_NUM] = 34;
        $capacity[$i][ECL_M][EM_ALP] = 20;
        $capacity[$i][ECL_M][EM_BIN] = 14;
        $capacity[$i][ECL_M][EM_JAP] = 8;
        $capacity[$i][ECL_Q][EM_NUM] = 27;
        $capacity[$i][ECL_Q][EM_ALP] = 16;
        $capacity[$i][ECL_Q][EM_BIN] = 11;
        $capacity[$i][ECL_Q][EM_JAP] = 7;
        $capacity[$i][ECL_H][EM_NUM] = 17;
        $capacity[$i][ECL_H][EM_ALP] = 10;
        $capacity[$i][ECL_H][EM_BIN] = 7;
        $capacity[$i][ECL_H][EM_JAP] = 4;
        $capacity[$i][ECL_L][DATA_BITS] = 152;
        $capacity[$i][ECL_M][DATA_BITS] = 128;
        $capacity[$i][ECL_Q][DATA_BITS] = 104;
        $capacity[$i][ECL_H][DATA_BITS] = 72;
        //Version 2
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 77;
        $capacity[$i][ECL_L][EM_ALP] = 47;
        $capacity[$i][ECL_L][EM_BIN] = 32;
        $capacity[$i][ECL_L][EM_JAP] = 20;
        $capacity[$i][ECL_M][EM_NUM] = 63;
        $capacity[$i][ECL_M][EM_ALP] = 38;
        $capacity[$i][ECL_M][EM_BIN] = 26;
        $capacity[$i][ECL_M][EM_JAP] = 16;
        $capacity[$i][ECL_Q][EM_NUM] = 48;
        $capacity[$i][ECL_Q][EM_ALP] = 29;
        $capacity[$i][ECL_Q][EM_BIN] = 20;
        $capacity[$i][ECL_Q][EM_JAP] = 12;
        $capacity[$i][ECL_H][EM_NUM] = 34;
        $capacity[$i][ECL_H][EM_ALP] = 20;
        $capacity[$i][ECL_H][EM_BIN] = 14;
        $capacity[$i][ECL_H][EM_JAP] = 8;
        $capacity[$i][ECL_L][DATA_BITS] = 272;
        $capacity[$i][ECL_M][DATA_BITS] = 224;
        $capacity[$i][ECL_Q][DATA_BITS] = 176;
        $capacity[$i][ECL_H][DATA_BITS] = 128;
        //Version 3
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 127;
        $capacity[$i][ECL_L][EM_ALP] = 77;
        $capacity[$i][ECL_L][EM_BIN] = 53;
        $capacity[$i][ECL_L][EM_JAP] = 32;
        $capacity[$i][ECL_M][EM_NUM] = 101;
        $capacity[$i][ECL_M][EM_ALP] = 61;
        $capacity[$i][ECL_M][EM_BIN] = 42;
        $capacity[$i][ECL_M][EM_JAP] = 26;
        $capacity[$i][ECL_Q][EM_NUM] = 77;
        $capacity[$i][ECL_Q][EM_ALP] = 47;
        $capacity[$i][ECL_Q][EM_BIN] = 32;
        $capacity[$i][ECL_Q][EM_JAP] = 20;
        $capacity[$i][ECL_H][EM_NUM] = 58;
        $capacity[$i][ECL_H][EM_ALP] = 35;
        $capacity[$i][ECL_H][EM_BIN] = 24;
        $capacity[$i][ECL_H][EM_JAP] = 15;   
        $capacity[$i][ECL_L][DATA_BITS] = 440;
        $capacity[$i][ECL_M][DATA_BITS] = 352;
        $capacity[$i][ECL_Q][DATA_BITS] = 272;
        $capacity[$i][ECL_H][DATA_BITS] = 208;        
        //Version 4
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 187;
        $capacity[$i][ECL_L][EM_ALP] = 114;
        $capacity[$i][ECL_L][EM_BIN] = 78;
        $capacity[$i][ECL_L][EM_JAP] = 48;
        $capacity[$i][ECL_M][EM_NUM] = 149;
        $capacity[$i][ECL_M][EM_ALP] = 90;
        $capacity[$i][ECL_M][EM_BIN] = 62;
        $capacity[$i][ECL_M][EM_JAP] = 38;
        $capacity[$i][ECL_Q][EM_NUM] = 111;
        $capacity[$i][ECL_Q][EM_ALP] = 67;
        $capacity[$i][ECL_Q][EM_BIN] = 46;
        $capacity[$i][ECL_Q][EM_JAP] = 28;
        $capacity[$i][ECL_H][EM_NUM] = 82;
        $capacity[$i][ECL_H][EM_ALP] = 50;
        $capacity[$i][ECL_H][EM_BIN] = 34;
        $capacity[$i][ECL_H][EM_JAP] = 21;      
        $capacity[$i][ECL_L][DATA_BITS] = 640;
        $capacity[$i][ECL_M][DATA_BITS] = 512;
        $capacity[$i][ECL_Q][DATA_BITS] = 384;
        $capacity[$i][ECL_H][DATA_BITS] = 288;
        //Version 5
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 255;
        $capacity[$i][ECL_L][EM_ALP] = 154;
        $capacity[$i][ECL_L][EM_BIN] = 106;
        $capacity[$i][ECL_L][EM_JAP] = 65;
        $capacity[$i][ECL_M][EM_NUM] = 202;
        $capacity[$i][ECL_M][EM_ALP] = 122;
        $capacity[$i][ECL_M][EM_BIN] = 84;
        $capacity[$i][ECL_M][EM_JAP] = 52;
        $capacity[$i][ECL_Q][EM_NUM] = 144;
        $capacity[$i][ECL_Q][EM_ALP] = 87;
        $capacity[$i][ECL_Q][EM_BIN] = 60;
        $capacity[$i][ECL_Q][EM_JAP] = 37;
        $capacity[$i][ECL_H][EM_NUM] = 106;
        $capacity[$i][ECL_H][EM_ALP] = 64;
        $capacity[$i][ECL_H][EM_BIN] = 44;
        $capacity[$i][ECL_H][EM_JAP] = 27;   
        $capacity[$i][ECL_L][DATA_BITS] = 864;
        $capacity[$i][ECL_M][DATA_BITS] = 688;
        $capacity[$i][ECL_Q][DATA_BITS] = 496;
        $capacity[$i][ECL_H][DATA_BITS] = 368;        
        //Version 6
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 322;
        $capacity[$i][ECL_L][EM_ALP] = 195;
        $capacity[$i][ECL_L][EM_BIN] = 134;
        $capacity[$i][ECL_L][EM_JAP] = 82;
        $capacity[$i][ECL_M][EM_NUM] = 255;
        $capacity[$i][ECL_M][EM_ALP] = 154;
        $capacity[$i][ECL_M][EM_BIN] = 106;
        $capacity[$i][ECL_M][EM_JAP] = 65;
        $capacity[$i][ECL_Q][EM_NUM] = 178;
        $capacity[$i][ECL_Q][EM_ALP] = 108;
        $capacity[$i][ECL_Q][EM_BIN] = 74;
        $capacity[$i][ECL_Q][EM_JAP] = 45;
        $capacity[$i][ECL_H][EM_NUM] = 139;
        $capacity[$i][ECL_H][EM_ALP] = 84;
        $capacity[$i][ECL_H][EM_BIN] = 58;
        $capacity[$i][ECL_H][EM_JAP] = 36;   
        $capacity[$i][ECL_L][DATA_BITS] = 1088;
        $capacity[$i][ECL_M][DATA_BITS] = 864;
        $capacity[$i][ECL_Q][DATA_BITS] = 608;
        $capacity[$i][ECL_H][DATA_BITS] = 480;        
        //Version 7
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 370;
        $capacity[$i][ECL_L][EM_ALP] = 224;
        $capacity[$i][ECL_L][EM_BIN] = 154;
        $capacity[$i][ECL_L][EM_JAP] = 95;
        $capacity[$i][ECL_M][EM_NUM] = 293;
        $capacity[$i][ECL_M][EM_ALP] = 178;
        $capacity[$i][ECL_M][EM_BIN] = 122;
        $capacity[$i][ECL_M][EM_JAP] = 75;
        $capacity[$i][ECL_Q][EM_NUM] = 207;
        $capacity[$i][ECL_Q][EM_ALP] = 125;
        $capacity[$i][ECL_Q][EM_BIN] = 86;
        $capacity[$i][ECL_Q][EM_JAP] = 53;
        $capacity[$i][ECL_H][EM_NUM] = 154;
        $capacity[$i][ECL_H][EM_ALP] = 93;
        $capacity[$i][ECL_H][EM_BIN] = 64;
        $capacity[$i][ECL_H][EM_JAP] = 39;   
        $capacity[$i][ECL_L][DATA_BITS] = 1284;
        $capacity[$i][ECL_M][DATA_BITS] = 992;
        $capacity[$i][ECL_Q][DATA_BITS] = 704;
        $capacity[$i][ECL_H][DATA_BITS] = 528;
        //Version 8
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 461;
        $capacity[$i][ECL_L][EM_ALP] = 279;
        $capacity[$i][ECL_L][EM_BIN] = 192;
        $capacity[$i][ECL_L][EM_JAP] = 118;
        $capacity[$i][ECL_M][EM_NUM] = 365;
        $capacity[$i][ECL_M][EM_ALP] = 221;
        $capacity[$i][ECL_M][EM_BIN] = 152;
        $capacity[$i][ECL_M][EM_JAP] = 93;
        $capacity[$i][ECL_Q][EM_NUM] = 259;
        $capacity[$i][ECL_Q][EM_ALP] = 157;
        $capacity[$i][ECL_Q][EM_BIN] = 108;
        $capacity[$i][ECL_Q][EM_JAP] = 66;
        $capacity[$i][ECL_H][EM_NUM] = 202;
        $capacity[$i][ECL_H][EM_ALP] = 122;
        $capacity[$i][ECL_H][EM_BIN] = 84;
        $capacity[$i][ECL_H][EM_JAP] = 52;              
        $capacity[$i][ECL_L][DATA_BITS] = 1552;
        $capacity[$i][ECL_M][DATA_BITS] = 1232;
        $capacity[$i][ECL_Q][DATA_BITS] = 880;
        $capacity[$i][ECL_H][DATA_BITS] = 688;        
        //Version 9
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 552;
        $capacity[$i][ECL_L][EM_ALP] = 335;
        $capacity[$i][ECL_L][EM_BIN] = 230;
        $capacity[$i][ECL_L][EM_JAP] = 141;
        $capacity[$i][ECL_M][EM_NUM] = 432;
        $capacity[$i][ECL_M][EM_ALP] = 262;
        $capacity[$i][ECL_M][EM_BIN] = 180;
        $capacity[$i][ECL_M][EM_JAP] = 111;
        $capacity[$i][ECL_Q][EM_NUM] = 312;
        $capacity[$i][ECL_Q][EM_ALP] = 189;
        $capacity[$i][ECL_Q][EM_BIN] = 130;
        $capacity[$i][ECL_Q][EM_JAP] = 80;
        $capacity[$i][ECL_H][EM_NUM] = 235;
        $capacity[$i][ECL_H][EM_ALP] = 143;
        $capacity[$i][ECL_H][EM_BIN] = 98;
        $capacity[$i][ECL_H][EM_JAP] = 60;   
        $capacity[$i][ECL_L][DATA_BITS] = 1856;
        $capacity[$i][ECL_M][DATA_BITS] = 1456;
        $capacity[$i][ECL_Q][DATA_BITS] = 1056;
        $capacity[$i][ECL_H][DATA_BITS] = 800;        
        //Version 10
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 652;
        $capacity[$i][ECL_L][EM_ALP] = 395;
        $capacity[$i][ECL_L][EM_BIN] = 271;
        $capacity[$i][ECL_L][EM_JAP] = 167;
        $capacity[$i][ECL_M][EM_NUM] = 513;
        $capacity[$i][ECL_M][EM_ALP] = 311;
        $capacity[$i][ECL_M][EM_BIN] = 213;
        $capacity[$i][ECL_M][EM_JAP] = 131;
        $capacity[$i][ECL_Q][EM_NUM] = 364;
        $capacity[$i][ECL_Q][EM_ALP] = 221;
        $capacity[$i][ECL_Q][EM_BIN] = 151;
        $capacity[$i][ECL_Q][EM_JAP] = 93;
        $capacity[$i][ECL_H][EM_NUM] = 288;
        $capacity[$i][ECL_H][EM_ALP] = 174;
        $capacity[$i][ECL_H][EM_BIN] = 119;
        $capacity[$i][ECL_H][EM_JAP] = 74; 
        $capacity[$i][ECL_L][DATA_BITS] = 2192;
        $capacity[$i][ECL_M][DATA_BITS] = 1728;
        $capacity[$i][ECL_Q][DATA_BITS] = 1232;
        $capacity[$i][ECL_H][DATA_BITS] = 976;                
        //Version 11
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 772;
        $capacity[$i][ECL_L][EM_ALP] = 468;
        $capacity[$i][ECL_L][EM_BIN] = 321;
        $capacity[$i][ECL_L][EM_JAP] = 198;
        $capacity[$i][ECL_M][EM_NUM] = 604;
        $capacity[$i][ECL_M][EM_ALP] = 366;
        $capacity[$i][ECL_M][EM_BIN] = 251;
        $capacity[$i][ECL_M][EM_JAP] = 155;
        $capacity[$i][ECL_Q][EM_NUM] = 427;
        $capacity[$i][ECL_Q][EM_ALP] = 259;
        $capacity[$i][ECL_Q][EM_BIN] = 177;
        $capacity[$i][ECL_Q][EM_JAP] = 109;
        $capacity[$i][ECL_H][EM_NUM] = 331;
        $capacity[$i][ECL_H][EM_ALP] = 200;
        $capacity[$i][ECL_H][EM_BIN] = 137;
        $capacity[$i][ECL_H][EM_JAP] = 85;   
        $capacity[$i][ECL_L][DATA_BITS] = 2592;
        $capacity[$i][ECL_M][DATA_BITS] = 2032;
        $capacity[$i][ECL_Q][DATA_BITS] = 1440;
        $capacity[$i][ECL_H][DATA_BITS] = 1120;                
        //Version 12
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 883;
        $capacity[$i][ECL_L][EM_ALP] = 535;
        $capacity[$i][ECL_L][EM_BIN] = 367;
        $capacity[$i][ECL_L][EM_JAP] = 226;
        $capacity[$i][ECL_M][EM_NUM] = 691;
        $capacity[$i][ECL_M][EM_ALP] = 419;
        $capacity[$i][ECL_M][EM_BIN] = 287;
        $capacity[$i][ECL_M][EM_JAP] = 177;
        $capacity[$i][ECL_Q][EM_NUM] = 489;
        $capacity[$i][ECL_Q][EM_ALP] = 296;
        $capacity[$i][ECL_Q][EM_BIN] = 203;
        $capacity[$i][ECL_Q][EM_JAP] = 125;
        $capacity[$i][ECL_H][EM_NUM] = 374;
        $capacity[$i][ECL_H][EM_ALP] = 227;
        $capacity[$i][ECL_H][EM_BIN] = 155;
        $capacity[$i][ECL_H][EM_JAP] = 96;    
        $capacity[$i][ECL_L][DATA_BITS] = 2960;
        $capacity[$i][ECL_M][DATA_BITS] = 2320;
        $capacity[$i][ECL_Q][DATA_BITS] = 1648;
        $capacity[$i][ECL_H][DATA_BITS] = 1264;                
        //Version 13
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 1022;
        $capacity[$i][ECL_L][EM_ALP] = 619;
        $capacity[$i][ECL_L][EM_BIN] = 425;
        $capacity[$i][ECL_L][EM_JAP] = 262;
        $capacity[$i][ECL_M][EM_NUM] = 796;
        $capacity[$i][ECL_M][EM_ALP] = 483;
        $capacity[$i][ECL_M][EM_BIN] = 331;
        $capacity[$i][ECL_M][EM_JAP] = 204;
        $capacity[$i][ECL_Q][EM_NUM] = 580;
        $capacity[$i][ECL_Q][EM_ALP] = 352;
        $capacity[$i][ECL_Q][EM_BIN] = 241;
        $capacity[$i][ECL_Q][EM_JAP] = 149;
        $capacity[$i][ECL_H][EM_NUM] = 427;
        $capacity[$i][ECL_H][EM_ALP] = 259;
        $capacity[$i][ECL_H][EM_BIN] = 177;
        $capacity[$i][ECL_H][EM_JAP] = 109;
        $capacity[$i][ECL_L][DATA_BITS] = 3424;
        $capacity[$i][ECL_M][DATA_BITS] = 2672;
        $capacity[$i][ECL_Q][DATA_BITS] = 1952;
        $capacity[$i][ECL_H][DATA_BITS] = 1440;                
        //Version 14
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 1101;
        $capacity[$i][ECL_L][EM_ALP] = 667;
        $capacity[$i][ECL_L][EM_BIN] = 458;
        $capacity[$i][ECL_L][EM_JAP] = 282;
        $capacity[$i][ECL_M][EM_NUM] = 871;
        $capacity[$i][ECL_M][EM_ALP] = 528;
        $capacity[$i][ECL_M][EM_BIN] = 362;
        $capacity[$i][ECL_M][EM_JAP] = 223;
        $capacity[$i][ECL_Q][EM_NUM] = 621;
        $capacity[$i][ECL_Q][EM_ALP] = 376;
        $capacity[$i][ECL_Q][EM_BIN] = 258;
        $capacity[$i][ECL_Q][EM_JAP] = 159;
        $capacity[$i][ECL_H][EM_NUM] = 468;
        $capacity[$i][ECL_H][EM_ALP] = 283;
        $capacity[$i][ECL_H][EM_BIN] = 194;
        $capacity[$i][ECL_H][EM_JAP] = 120;   
        $capacity[$i][ECL_L][DATA_BITS] = 3688;
        $capacity[$i][ECL_M][DATA_BITS] = 2920;
        $capacity[$i][ECL_Q][DATA_BITS] = 2088;
        $capacity[$i][ECL_H][DATA_BITS] = 1576;                
        //Version 15
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 1250;
        $capacity[$i][ECL_L][EM_ALP] = 758;
        $capacity[$i][ECL_L][EM_BIN] = 520;
        $capacity[$i][ECL_L][EM_JAP] = 320;
        $capacity[$i][ECL_M][EM_NUM] = 991;
        $capacity[$i][ECL_M][EM_ALP] = 600;
        $capacity[$i][ECL_M][EM_BIN] = 412;
        $capacity[$i][ECL_M][EM_JAP] = 254;
        $capacity[$i][ECL_Q][EM_NUM] = 703;
        $capacity[$i][ECL_Q][EM_ALP] = 426;
        $capacity[$i][ECL_Q][EM_BIN] = 292;
        $capacity[$i][ECL_Q][EM_JAP] = 180;
        $capacity[$i][ECL_H][EM_NUM] = 530;
        $capacity[$i][ECL_H][EM_ALP] = 321;
        $capacity[$i][ECL_H][EM_BIN] = 220;
        $capacity[$i][ECL_H][EM_JAP] = 136;
        $capacity[$i][ECL_L][DATA_BITS] = 4184;
        $capacity[$i][ECL_M][DATA_BITS] = 3320;
        $capacity[$i][ECL_Q][DATA_BITS] = 2360;
        $capacity[$i][ECL_H][DATA_BITS] = 1784;                
        //Version 16
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 1408;
        $capacity[$i][ECL_L][EM_ALP] = 854;
        $capacity[$i][ECL_L][EM_BIN] = 586;
        $capacity[$i][ECL_L][EM_JAP] = 361;
        $capacity[$i][ECL_M][EM_NUM] = 1082;
        $capacity[$i][ECL_M][EM_ALP] = 656;
        $capacity[$i][ECL_M][EM_BIN] = 450;
        $capacity[$i][ECL_M][EM_JAP] = 277;
        $capacity[$i][ECL_Q][EM_NUM] = 775;
        $capacity[$i][ECL_Q][EM_ALP] = 470;
        $capacity[$i][ECL_Q][EM_BIN] = 322;
        $capacity[$i][ECL_Q][EM_JAP] = 198;
        $capacity[$i][ECL_H][EM_NUM] = 602;
        $capacity[$i][ECL_H][EM_ALP] = 365;
        $capacity[$i][ECL_H][EM_BIN] = 250;
        $capacity[$i][ECL_H][EM_JAP] = 154;   
        $capacity[$i][ECL_L][DATA_BITS] = 4712;
        $capacity[$i][ECL_M][DATA_BITS] = 3624;
        $capacity[$i][ECL_Q][DATA_BITS] = 2600;
        $capacity[$i][ECL_H][DATA_BITS] = 2024;                
        //Version 17
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 1548;
        $capacity[$i][ECL_L][EM_ALP] = 938;
        $capacity[$i][ECL_L][EM_BIN] = 644;
        $capacity[$i][ECL_L][EM_JAP] = 397;
        $capacity[$i][ECL_M][EM_NUM] = 1212;
        $capacity[$i][ECL_M][EM_ALP] = 734;
        $capacity[$i][ECL_M][EM_BIN] = 504;
        $capacity[$i][ECL_M][EM_JAP] = 310;
        $capacity[$i][ECL_Q][EM_NUM] = 876;
        $capacity[$i][ECL_Q][EM_ALP] = 531;
        $capacity[$i][ECL_Q][EM_BIN] = 364;
        $capacity[$i][ECL_Q][EM_JAP] = 224;
        $capacity[$i][ECL_H][EM_NUM] = 674;
        $capacity[$i][ECL_H][EM_ALP] = 408;
        $capacity[$i][ECL_H][EM_BIN] = 280;
        $capacity[$i][ECL_H][EM_JAP] = 173;
        $capacity[$i][ECL_L][DATA_BITS] = 5176;
        $capacity[$i][ECL_M][DATA_BITS] = 4056;
        $capacity[$i][ECL_Q][DATA_BITS] = 2936;
        $capacity[$i][ECL_H][DATA_BITS] = 2264;        
        //Version 18
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 1725;
        $capacity[$i][ECL_L][EM_ALP] = 1046;
        $capacity[$i][ECL_L][EM_BIN] = 718;
        $capacity[$i][ECL_L][EM_JAP] = 442;
        $capacity[$i][ECL_M][EM_NUM] = 1346;
        $capacity[$i][ECL_M][EM_ALP] = 816;
        $capacity[$i][ECL_M][EM_BIN] = 560;
        $capacity[$i][ECL_M][EM_JAP] = 345;
        $capacity[$i][ECL_Q][EM_NUM] = 998;
        $capacity[$i][ECL_Q][EM_ALP] = 574;
        $capacity[$i][ECL_Q][EM_BIN] = 394;
        $capacity[$i][ECL_Q][EM_JAP] = 243;
        $capacity[$i][ECL_H][EM_NUM] = 746;
        $capacity[$i][ECL_H][EM_ALP] = 452;
        $capacity[$i][ECL_H][EM_BIN] = 310;
        $capacity[$i][ECL_H][EM_JAP] = 191;    
        $capacity[$i][ECL_L][DATA_BITS] = 5768;
        $capacity[$i][ECL_M][DATA_BITS] = 4504;
        $capacity[$i][ECL_Q][DATA_BITS] = 3176;
        $capacity[$i][ECL_H][DATA_BITS] = 2504;                
        //Version 19
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 1903;
        $capacity[$i][ECL_L][EM_ALP] = 1153;
        $capacity[$i][ECL_L][EM_BIN] = 792;
        $capacity[$i][ECL_L][EM_JAP] = 488;
        $capacity[$i][ECL_M][EM_NUM] = 1500;
        $capacity[$i][ECL_M][EM_ALP] = 909;
        $capacity[$i][ECL_M][EM_BIN] = 624;
        $capacity[$i][ECL_M][EM_JAP] = 384;
        $capacity[$i][ECL_Q][EM_NUM] = 1063;
        $capacity[$i][ECL_Q][EM_ALP] = 644;
        $capacity[$i][ECL_Q][EM_BIN] = 442;
        $capacity[$i][ECL_Q][EM_JAP] = 272;
        $capacity[$i][ECL_H][EM_NUM] = 813;
        $capacity[$i][ECL_H][EM_ALP] = 493;
        $capacity[$i][ECL_H][EM_BIN] = 338;
        $capacity[$i][ECL_H][EM_JAP] = 208;      
        $capacity[$i][ECL_L][DATA_BITS] = 6360;
        $capacity[$i][ECL_M][DATA_BITS] = 5016;
        $capacity[$i][ECL_Q][DATA_BITS] = 3560;
        $capacity[$i][ECL_H][DATA_BITS] = 2728;                
        //Version 20
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 2061;
        $capacity[$i][ECL_L][EM_ALP] = 1249;
        $capacity[$i][ECL_L][EM_BIN] = 858;
        $capacity[$i][ECL_L][EM_JAP] = 528;
        $capacity[$i][ECL_M][EM_NUM] = 1600;
        $capacity[$i][ECL_M][EM_ALP] = 970;
        $capacity[$i][ECL_M][EM_BIN] = 666;
        $capacity[$i][ECL_M][EM_JAP] = 410;
        $capacity[$i][ECL_Q][EM_NUM] = 1159;
        $capacity[$i][ECL_Q][EM_ALP] = 702;
        $capacity[$i][ECL_Q][EM_BIN] = 482;
        $capacity[$i][ECL_Q][EM_JAP] = 297;
        $capacity[$i][ECL_H][EM_NUM] = 919;
        $capacity[$i][ECL_H][EM_ALP] = 557;
        $capacity[$i][ECL_H][EM_BIN] = 382;
        $capacity[$i][ECL_H][EM_JAP] = 235; 
        $capacity[$i][ECL_L][DATA_BITS] = 6888;
        $capacity[$i][ECL_M][DATA_BITS] = 5352;
        $capacity[$i][ECL_Q][DATA_BITS] = 3880;
        $capacity[$i][ECL_H][DATA_BITS] = 3080;       
        //Version 21
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 2232;
        $capacity[$i][ECL_L][EM_ALP] = 1352;
        $capacity[$i][ECL_L][EM_BIN] = 929;
        $capacity[$i][ECL_L][EM_JAP] = 572;
        $capacity[$i][ECL_M][EM_NUM] = 1708;
        $capacity[$i][ECL_M][EM_ALP] = 1035;
        $capacity[$i][ECL_M][EM_BIN] = 711;
        $capacity[$i][ECL_M][EM_JAP] = 438;
        $capacity[$i][ECL_Q][EM_NUM] = 1224;
        $capacity[$i][ECL_Q][EM_ALP] = 742;
        $capacity[$i][ECL_Q][EM_BIN] = 509;
        $capacity[$i][ECL_Q][EM_JAP] = 314;
        $capacity[$i][ECL_H][EM_NUM] = 969;
        $capacity[$i][ECL_H][EM_ALP] = 587;
        $capacity[$i][ECL_H][EM_BIN] = 403;
        $capacity[$i][ECL_H][EM_JAP] = 248;      
        $capacity[$i][ECL_L][DATA_BITS] = 7456;
        $capacity[$i][ECL_M][DATA_BITS] = 5712;
        $capacity[$i][ECL_Q][DATA_BITS] = 4096;
        $capacity[$i][ECL_H][DATA_BITS] = 3248;                
        //Version 22
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 2409;
        $capacity[$i][ECL_L][EM_ALP] = 1460;
        $capacity[$i][ECL_L][EM_BIN] = 1003;
        $capacity[$i][ECL_L][EM_JAP] = 618;
        $capacity[$i][ECL_M][EM_NUM] = 1872;
        $capacity[$i][ECL_M][EM_ALP] = 1134;
        $capacity[$i][ECL_M][EM_BIN] = 779;
        $capacity[$i][ECL_M][EM_JAP] = 480;
        $capacity[$i][ECL_Q][EM_NUM] = 1358;
        $capacity[$i][ECL_Q][EM_ALP] = 823;
        $capacity[$i][ECL_Q][EM_BIN] = 565;
        $capacity[$i][ECL_Q][EM_JAP] = 348;
        $capacity[$i][ECL_H][EM_NUM] = 1056;
        $capacity[$i][ECL_H][EM_ALP] = 640;
        $capacity[$i][ECL_H][EM_BIN] = 439;
        $capacity[$i][ECL_H][EM_JAP] = 270;       
        $capacity[$i][ECL_L][DATA_BITS] = 8048;
        $capacity[$i][ECL_M][DATA_BITS] = 6256;
        $capacity[$i][ECL_Q][DATA_BITS] = 4544;
        $capacity[$i][ECL_H][DATA_BITS] = 3536;                
        //Version 23
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 2620;
        $capacity[$i][ECL_L][EM_ALP] = 1588;
        $capacity[$i][ECL_L][EM_BIN] = 1091;
        $capacity[$i][ECL_L][EM_JAP] = 672;
        $capacity[$i][ECL_M][EM_NUM] = 2059;
        $capacity[$i][ECL_M][EM_ALP] = 1248;
        $capacity[$i][ECL_M][EM_BIN] = 857;
        $capacity[$i][ECL_M][EM_JAP] = 528;
        $capacity[$i][ECL_Q][EM_NUM] = 1468;
        $capacity[$i][ECL_Q][EM_ALP] = 890;
        $capacity[$i][ECL_Q][EM_BIN] = 611;
        $capacity[$i][ECL_Q][EM_JAP] = 376;
        $capacity[$i][ECL_H][EM_NUM] = 1108;
        $capacity[$i][ECL_H][EM_ALP] = 672;
        $capacity[$i][ECL_H][EM_BIN] = 461;
        $capacity[$i][ECL_H][EM_JAP] = 284;  
        $capacity[$i][ECL_L][DATA_BITS] = 8752;
        $capacity[$i][ECL_M][DATA_BITS] = 6880;
        $capacity[$i][ECL_Q][DATA_BITS] = 4912;
        $capacity[$i][ECL_H][DATA_BITS] = 3712;                
        //Version 24
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 2812;
        $capacity[$i][ECL_L][EM_ALP] = 1704;
        $capacity[$i][ECL_L][EM_BIN] = 1171;
        $capacity[$i][ECL_L][EM_JAP] = 721;
        $capacity[$i][ECL_M][EM_NUM] = 2188;
        $capacity[$i][ECL_M][EM_ALP] = 1326;
        $capacity[$i][ECL_M][EM_BIN] = 911;
        $capacity[$i][ECL_M][EM_JAP] = 561;
        $capacity[$i][ECL_Q][EM_NUM] = 1588;
        $capacity[$i][ECL_Q][EM_ALP] = 963;
        $capacity[$i][ECL_Q][EM_BIN] = 661;
        $capacity[$i][ECL_Q][EM_JAP] = 407;
        $capacity[$i][ECL_H][EM_NUM] = 1228;
        $capacity[$i][ECL_H][EM_ALP] = 744;
        $capacity[$i][ECL_H][EM_BIN] = 511;
        $capacity[$i][ECL_H][EM_JAP] = 315;     
        $capacity[$i][ECL_L][DATA_BITS] = 9392;
        $capacity[$i][ECL_M][DATA_BITS] = 7312;
        $capacity[$i][ECL_Q][DATA_BITS] = 5312;
        $capacity[$i][ECL_H][DATA_BITS] = 4112;                
        //Version 25
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 3057;
        $capacity[$i][ECL_L][EM_ALP] = 1853;
        $capacity[$i][ECL_L][EM_BIN] = 1273;
        $capacity[$i][ECL_L][EM_JAP] = 784;
        $capacity[$i][ECL_M][EM_NUM] = 2395;
        $capacity[$i][ECL_M][EM_ALP] = 1451;
        $capacity[$i][ECL_M][EM_BIN] = 997;
        $capacity[$i][ECL_M][EM_JAP] = 614;
        $capacity[$i][ECL_Q][EM_NUM] = 1718;
        $capacity[$i][ECL_Q][EM_ALP] = 1041;
        $capacity[$i][ECL_Q][EM_BIN] = 715;
        $capacity[$i][ECL_Q][EM_JAP] = 440;
        $capacity[$i][ECL_H][EM_NUM] = 1286;
        $capacity[$i][ECL_H][EM_ALP] = 779;
        $capacity[$i][ECL_H][EM_BIN] = 535;
        $capacity[$i][ECL_H][EM_JAP] = 330;          
        $capacity[$i][ECL_L][DATA_BITS] = 10208;
        $capacity[$i][ECL_M][DATA_BITS] = 8000;
        $capacity[$i][ECL_Q][DATA_BITS] = 5744;
        $capacity[$i][ECL_H][DATA_BITS] = 4304;                
        //Version 26
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 3283;
        $capacity[$i][ECL_L][EM_ALP] = 1990;
        $capacity[$i][ECL_L][EM_BIN] = 1367;
        $capacity[$i][ECL_L][EM_JAP] = 842;
        $capacity[$i][ECL_M][EM_NUM] = 2544;
        $capacity[$i][ECL_M][EM_ALP] = 1542;
        $capacity[$i][ECL_M][EM_BIN] = 1059;
        $capacity[$i][ECL_M][EM_JAP] = 652;
        $capacity[$i][ECL_Q][EM_NUM] = 1804;
        $capacity[$i][ECL_Q][EM_ALP] = 1094;
        $capacity[$i][ECL_Q][EM_BIN] = 751;
        $capacity[$i][ECL_Q][EM_JAP] = 462;
        $capacity[$i][ECL_H][EM_NUM] = 1425;
        $capacity[$i][ECL_H][EM_ALP] = 864;
        $capacity[$i][ECL_H][EM_BIN] = 593;
        $capacity[$i][ECL_H][EM_JAP] = 365;      
        $capacity[$i][ECL_L][DATA_BITS] = 10960;
        $capacity[$i][ECL_M][DATA_BITS] = 8496;
        $capacity[$i][ECL_Q][DATA_BITS] = 6032;
        $capacity[$i][ECL_H][DATA_BITS] = 4768;                
        //Version 27
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 3517;
        $capacity[$i][ECL_L][EM_ALP] = 2132;
        $capacity[$i][ECL_L][EM_BIN] = 1465;
        $capacity[$i][ECL_L][EM_JAP] = 902;
        $capacity[$i][ECL_M][EM_NUM] = 2701;
        $capacity[$i][ECL_M][EM_ALP] = 1637;
        $capacity[$i][ECL_M][EM_BIN] = 1125;
        $capacity[$i][ECL_M][EM_JAP] = 692;
        $capacity[$i][ECL_Q][EM_NUM] = 1933;
        $capacity[$i][ECL_Q][EM_ALP] = 1172;
        $capacity[$i][ECL_Q][EM_BIN] = 805;
        $capacity[$i][ECL_Q][EM_JAP] = 496;
        $capacity[$i][ECL_H][EM_NUM] = 1501;
        $capacity[$i][ECL_H][EM_ALP] = 910;
        $capacity[$i][ECL_H][EM_BIN] = 625;
        $capacity[$i][ECL_H][EM_JAP] = 385;     
        $capacity[$i][ECL_L][DATA_BITS] = 11744;
        $capacity[$i][ECL_M][DATA_BITS] = 9024;
        $capacity[$i][ECL_Q][DATA_BITS] = 6464;
        $capacity[$i][ECL_H][DATA_BITS] = 5024;                
        //Version 28
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 3669;
        $capacity[$i][ECL_L][EM_ALP] = 2223;
        $capacity[$i][ECL_L][EM_BIN] = 1528;
        $capacity[$i][ECL_L][EM_JAP] = 940;
        $capacity[$i][ECL_M][EM_NUM] = 2857;
        $capacity[$i][ECL_M][EM_ALP] = 1732;
        $capacity[$i][ECL_M][EM_BIN] = 1190;
        $capacity[$i][ECL_M][EM_JAP] = 732;
        $capacity[$i][ECL_Q][EM_NUM] = 2085;
        $capacity[$i][ECL_Q][EM_ALP] = 1263;
        $capacity[$i][ECL_Q][EM_BIN] = 868;
        $capacity[$i][ECL_Q][EM_JAP] = 534;
        $capacity[$i][ECL_H][EM_NUM] = 1581;
        $capacity[$i][ECL_H][EM_ALP] = 958;
        $capacity[$i][ECL_H][EM_BIN] = 658;
        $capacity[$i][ECL_H][EM_JAP] = 405;          
        $capacity[$i][ECL_L][DATA_BITS] = 12248;
        $capacity[$i][ECL_M][DATA_BITS] = 9544;
        $capacity[$i][ECL_Q][DATA_BITS] = 6968;
        $capacity[$i][ECL_H][DATA_BITS] = 5288;                
        //Version 29
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 3909;
        $capacity[$i][ECL_L][EM_ALP] = 2369;
        $capacity[$i][ECL_L][EM_BIN] = 1628;
        $capacity[$i][ECL_L][EM_JAP] = 1002;
        $capacity[$i][ECL_M][EM_NUM] = 3035;
        $capacity[$i][ECL_M][EM_ALP] = 1839;
        $capacity[$i][ECL_M][EM_BIN] = 1264;
        $capacity[$i][ECL_M][EM_JAP] = 778;
        $capacity[$i][ECL_Q][EM_NUM] = 2181;
        $capacity[$i][ECL_Q][EM_ALP] = 1322;
        $capacity[$i][ECL_Q][EM_BIN] = 908;
        $capacity[$i][ECL_Q][EM_JAP] = 559;
        $capacity[$i][ECL_H][EM_NUM] = 1677;
        $capacity[$i][ECL_H][EM_ALP] = 1016;
        $capacity[$i][ECL_H][EM_BIN] = 698;
        $capacity[$i][ECL_H][EM_JAP] = 430;
        $capacity[$i][ECL_L][DATA_BITS] = 13048;
        $capacity[$i][ECL_M][DATA_BITS] = 10136;
        $capacity[$i][ECL_Q][DATA_BITS] = 7288;
        $capacity[$i][ECL_H][DATA_BITS] = 5608;                
        //Version 30
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 4158;
        $capacity[$i][ECL_L][EM_ALP] = 2520;
        $capacity[$i][ECL_L][EM_BIN] = 1732;
        $capacity[$i][ECL_L][EM_JAP] = 1066;
        $capacity[$i][ECL_M][EM_NUM] = 3289;
        $capacity[$i][ECL_M][EM_ALP] = 1994;
        $capacity[$i][ECL_M][EM_BIN] = 1370;
        $capacity[$i][ECL_M][EM_JAP] = 843;
        $capacity[$i][ECL_Q][EM_NUM] = 2358;
        $capacity[$i][ECL_Q][EM_ALP] = 1429;
        $capacity[$i][ECL_Q][EM_BIN] = 982;
        $capacity[$i][ECL_Q][EM_JAP] = 604;
        $capacity[$i][ECL_H][EM_NUM] = 1782;
        $capacity[$i][ECL_H][EM_ALP] = 1080;
        $capacity[$i][ECL_H][EM_BIN] = 742;
        $capacity[$i][ECL_H][EM_JAP] = 457;
        $capacity[$i][ECL_L][DATA_BITS] = 13880;
        $capacity[$i][ECL_M][DATA_BITS] = 10984;
        $capacity[$i][ECL_Q][DATA_BITS] = 7880;
        $capacity[$i][ECL_H][DATA_BITS] = 5960;                
        //Version 31
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 4417;
        $capacity[$i][ECL_L][EM_ALP] = 2677;
        $capacity[$i][ECL_L][EM_BIN] = 1840;
        $capacity[$i][ECL_L][EM_JAP] = 1132;
        $capacity[$i][ECL_M][EM_NUM] = 3486;
        $capacity[$i][ECL_M][EM_ALP] = 2113;
        $capacity[$i][ECL_M][EM_BIN] = 1452;
        $capacity[$i][ECL_M][EM_JAP] = 894;
        $capacity[$i][ECL_Q][EM_NUM] = 2473;
        $capacity[$i][ECL_Q][EM_ALP] = 1499;
        $capacity[$i][ECL_Q][EM_BIN] = 1030;
        $capacity[$i][ECL_Q][EM_JAP] = 634;
        $capacity[$i][ECL_H][EM_NUM] = 1897;
        $capacity[$i][ECL_H][EM_ALP] = 1150;
        $capacity[$i][ECL_H][EM_BIN] = 790;
        $capacity[$i][ECL_H][EM_JAP] = 486;   
        $capacity[$i][ECL_L][DATA_BITS] = 14744;
        $capacity[$i][ECL_M][DATA_BITS] = 11640;
        $capacity[$i][ECL_Q][DATA_BITS] = 8264;
        $capacity[$i][ECL_H][DATA_BITS] = 6344;                
        //Version 32
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 4686;
        $capacity[$i][ECL_L][EM_ALP] = 2840;
        $capacity[$i][ECL_L][EM_BIN] = 1952;
        $capacity[$i][ECL_L][EM_JAP] = 1201;
        $capacity[$i][ECL_M][EM_NUM] = 3693;
        $capacity[$i][ECL_M][EM_ALP] = 2238;
        $capacity[$i][ECL_M][EM_BIN] = 1538;
        $capacity[$i][ECL_M][EM_JAP] = 947;
        $capacity[$i][ECL_Q][EM_NUM] = 2670;
        $capacity[$i][ECL_Q][EM_ALP] = 1618;
        $capacity[$i][ECL_Q][EM_BIN] = 1112;
        $capacity[$i][ECL_Q][EM_JAP] = 684;
        $capacity[$i][ECL_H][EM_NUM] = 2022;
        $capacity[$i][ECL_H][EM_ALP] = 1226;
        $capacity[$i][ECL_H][EM_BIN] = 842;
        $capacity[$i][ECL_H][EM_JAP] = 518;           
        $capacity[$i][ECL_L][DATA_BITS] = 15640;
        $capacity[$i][ECL_M][DATA_BITS] = 12328;
        $capacity[$i][ECL_Q][DATA_BITS] = 8920;
        $capacity[$i][ECL_H][DATA_BITS] = 6760;                
        //Version 33
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 4965;
        $capacity[$i][ECL_L][EM_ALP] = 3009;
        $capacity[$i][ECL_L][EM_BIN] = 2068;
        $capacity[$i][ECL_L][EM_JAP] = 1273;
        $capacity[$i][ECL_M][EM_NUM] = 3909;
        $capacity[$i][ECL_M][EM_ALP] = 2369;
        $capacity[$i][ECL_M][EM_BIN] = 1628;
        $capacity[$i][ECL_M][EM_JAP] = 1002;
        $capacity[$i][ECL_Q][EM_NUM] = 2805;
        $capacity[$i][ECL_Q][EM_ALP] = 1700;
        $capacity[$i][ECL_Q][EM_BIN] = 1168;
        $capacity[$i][ECL_Q][EM_JAP] = 719;
        $capacity[$i][ECL_H][EM_NUM] = 2157;
        $capacity[$i][ECL_H][EM_ALP] = 1307;
        $capacity[$i][ECL_H][EM_BIN] = 898;
        $capacity[$i][ECL_H][EM_JAP] = 553;           
        $capacity[$i][ECL_L][DATA_BITS] = 16568;
        $capacity[$i][ECL_M][DATA_BITS] = 13048;
        $capacity[$i][ECL_Q][DATA_BITS] = 9368;
        $capacity[$i][ECL_H][DATA_BITS] = 7208;                
        //Version 34
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 5253;
        $capacity[$i][ECL_L][EM_ALP] = 3183;
        $capacity[$i][ECL_L][EM_BIN] = 2188;
        $capacity[$i][ECL_L][EM_JAP] = 1347;
        $capacity[$i][ECL_M][EM_NUM] = 4134;
        $capacity[$i][ECL_M][EM_ALP] = 2506;
        $capacity[$i][ECL_M][EM_BIN] = 1722;
        $capacity[$i][ECL_M][EM_JAP] = 1060;
        $capacity[$i][ECL_Q][EM_NUM] = 2949;
        $capacity[$i][ECL_Q][EM_ALP] = 1787;
        $capacity[$i][ECL_Q][EM_BIN] = 1228;
        $capacity[$i][ECL_Q][EM_JAP] = 756;
        $capacity[$i][ECL_H][EM_NUM] = 2301;
        $capacity[$i][ECL_H][EM_ALP] = 1394;
        $capacity[$i][ECL_H][EM_BIN] = 958;
        $capacity[$i][ECL_H][EM_JAP] = 590;
        $capacity[$i][ECL_L][DATA_BITS] = 17528;
        $capacity[$i][ECL_M][DATA_BITS] = 13800;
        $capacity[$i][ECL_Q][DATA_BITS] = 9848;
        $capacity[$i][ECL_H][DATA_BITS] = 7688;                
        //Version 35
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 5529;
        $capacity[$i][ECL_L][EM_ALP] = 3351;
        $capacity[$i][ECL_L][EM_BIN] = 2303;
        $capacity[$i][ECL_L][EM_JAP] = 1417;
        $capacity[$i][ECL_M][EM_NUM] = 4343;
        $capacity[$i][ECL_M][EM_ALP] = 2632;
        $capacity[$i][ECL_M][EM_BIN] = 1809;
        $capacity[$i][ECL_M][EM_JAP] = 1113;
        $capacity[$i][ECL_Q][EM_NUM] = 3081;
        $capacity[$i][ECL_Q][EM_ALP] = 1867;
        $capacity[$i][ECL_Q][EM_BIN] = 1283;
        $capacity[$i][ECL_Q][EM_JAP] = 790;
        $capacity[$i][ECL_H][EM_NUM] = 2361;
        $capacity[$i][ECL_H][EM_ALP] = 1431;
        $capacity[$i][ECL_H][EM_BIN] = 983;
        $capacity[$i][ECL_H][EM_JAP] = 605;
        $capacity[$i][ECL_L][DATA_BITS] = 18448;
        $capacity[$i][ECL_M][DATA_BITS] = 14496;
        $capacity[$i][ECL_Q][DATA_BITS] = 10288;
        $capacity[$i][ECL_H][DATA_BITS] = 7888;                
        //Version 36
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 5838;
        $capacity[$i][ECL_L][EM_ALP] = 3537;
        $capacity[$i][ECL_L][EM_BIN] = 2431;
        $capacity[$i][ECL_L][EM_JAP] = 1496;
        $capacity[$i][ECL_M][EM_NUM] = 4588;
        $capacity[$i][ECL_M][EM_ALP] = 2780;
        $capacity[$i][ECL_M][EM_BIN] = 1911;
        $capacity[$i][ECL_M][EM_JAP] = 1176;
        $capacity[$i][ECL_Q][EM_NUM] = 3244;
        $capacity[$i][ECL_Q][EM_ALP] = 1966;
        $capacity[$i][ECL_Q][EM_BIN] = 1351;
        $capacity[$i][ECL_Q][EM_JAP] = 832;
        $capacity[$i][ECL_H][EM_NUM] = 2524;
        $capacity[$i][ECL_H][EM_ALP] = 1530;
        $capacity[$i][ECL_H][EM_BIN] = 1051;
        $capacity[$i][ECL_H][EM_JAP] = 647;     
        $capacity[$i][ECL_L][DATA_BITS] = 19472;
        $capacity[$i][ECL_M][DATA_BITS] = 15312;
        $capacity[$i][ECL_Q][DATA_BITS] = 10832;
        $capacity[$i][ECL_H][DATA_BITS] = 8432;                
        //Version 37
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 6153;
        $capacity[$i][ECL_L][EM_ALP] = 3729;
        $capacity[$i][ECL_L][EM_BIN] = 2563;
        $capacity[$i][ECL_L][EM_JAP] = 1577;
        $capacity[$i][ECL_M][EM_NUM] = 4775;
        $capacity[$i][ECL_M][EM_ALP] = 2894;
        $capacity[$i][ECL_M][EM_BIN] = 1989;
        $capacity[$i][ECL_M][EM_JAP] = 1224;
        $capacity[$i][ECL_Q][EM_NUM] = 3417;
        $capacity[$i][ECL_Q][EM_ALP] = 2071;
        $capacity[$i][ECL_Q][EM_BIN] = 1423;
        $capacity[$i][ECL_Q][EM_JAP] = 876;
        $capacity[$i][ECL_H][EM_NUM] = 2625;
        $capacity[$i][ECL_H][EM_ALP] = 1591;
        $capacity[$i][ECL_H][EM_BIN] = 1093;
        $capacity[$i][ECL_H][EM_JAP] = 673;   
        $capacity[$i][ECL_L][DATA_BITS] = 20528;
        $capacity[$i][ECL_M][DATA_BITS] = 15936;
        $capacity[$i][ECL_Q][DATA_BITS] = 11408;
        $capacity[$i][ECL_H][DATA_BITS] = 8768;                
        //Version 38
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 6479;
        $capacity[$i][ECL_L][EM_ALP] = 3927;
        $capacity[$i][ECL_L][EM_BIN] = 2699;
        $capacity[$i][ECL_L][EM_JAP] = 1661;
        $capacity[$i][ECL_M][EM_NUM] = 5039;
        $capacity[$i][ECL_M][EM_ALP] = 3054;
        $capacity[$i][ECL_M][EM_BIN] = 2099;
        $capacity[$i][ECL_M][EM_JAP] = 1292;
        $capacity[$i][ECL_Q][EM_NUM] = 3599;
        $capacity[$i][ECL_Q][EM_ALP] = 2181;
        $capacity[$i][ECL_Q][EM_BIN] = 1499;
        $capacity[$i][ECL_Q][EM_JAP] = 923;
        $capacity[$i][ECL_H][EM_NUM] = 2735;
        $capacity[$i][ECL_H][EM_ALP] = 1658;
        $capacity[$i][ECL_H][EM_BIN] = 1139;
        $capacity[$i][ECL_H][EM_JAP] = 701;          
        $capacity[$i][ECL_L][DATA_BITS] = 21616;
        $capacity[$i][ECL_M][DATA_BITS] = 16816;
        $capacity[$i][ECL_Q][DATA_BITS] = 12016;
        $capacity[$i][ECL_H][DATA_BITS] = 9136;                
        //Version 39
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 6743;
        $capacity[$i][ECL_L][EM_ALP] = 4087;
        $capacity[$i][ECL_L][EM_BIN] = 2809;
        $capacity[$i][ECL_L][EM_JAP] = 1729;
        $capacity[$i][ECL_M][EM_NUM] = 5313;
        $capacity[$i][ECL_M][EM_ALP] = 3220;
        $capacity[$i][ECL_M][EM_BIN] = 2213;
        $capacity[$i][ECL_M][EM_JAP] = 1362;
        $capacity[$i][ECL_Q][EM_NUM] = 3791;
        $capacity[$i][ECL_Q][EM_ALP] = 2298;
        $capacity[$i][ECL_Q][EM_BIN] = 1579;
        $capacity[$i][ECL_Q][EM_JAP] = 972;
        $capacity[$i][ECL_H][EM_NUM] = 2927;
        $capacity[$i][ECL_H][EM_ALP] = 1774;
        $capacity[$i][ECL_H][EM_BIN] = 1219;
        $capacity[$i][ECL_H][EM_JAP] = 750;  
        $capacity[$i][ECL_L][DATA_BITS] = 22496;
        $capacity[$i][ECL_M][DATA_BITS] = 17728;
        $capacity[$i][ECL_Q][DATA_BITS] = 12656;
        $capacity[$i][ECL_H][DATA_BITS] = 9776;                
        //Version 40
        $i++;
        $capacity[$i][ECL_L][EM_NUM] = 7089;
        $capacity[$i][ECL_L][EM_ALP] = 4296;
        $capacity[$i][ECL_L][EM_BIN] = 2953;
        $capacity[$i][ECL_L][EM_JAP] = 1817;
        $capacity[$i][ECL_M][EM_NUM] = 5596;
        $capacity[$i][ECL_M][EM_ALP] = 3391;
        $capacity[$i][ECL_M][EM_BIN] = 2331;
        $capacity[$i][ECL_M][EM_JAP] = 1435;
        $capacity[$i][ECL_Q][EM_NUM] = 3993;
        $capacity[$i][ECL_Q][EM_ALP] = 2420;
        $capacity[$i][ECL_Q][EM_BIN] = 1663;
        $capacity[$i][ECL_Q][EM_JAP] = 1024;
        $capacity[$i][ECL_H][EM_NUM] = 3057;
        $capacity[$i][ECL_H][EM_ALP] = 1852;
        $capacity[$i][ECL_H][EM_BIN] = 1273;
        $capacity[$i][ECL_H][EM_JAP] = 784; 
        $capacity[$i][ECL_L][DATA_BITS] = 23648;
        $capacity[$i][ECL_M][DATA_BITS] = 18672;
        $capacity[$i][ECL_Q][DATA_BITS] = 13328;
        $capacity[$i][ECL_H][DATA_BITS] = 10208;     
        return $capacity[$version][$errorLevel][$encode];
    }
    //this method dtermines version and encode mode and reminder bits
    private function analysis(){
        if(DEBUG && (DEBUG_LEVEL & EQR_ANALYSIS)){
            $debug = true;
        }else{
            $debug = false;
        }                  
        $this->length = strlen($this->string);
        $this->encode = EM_NUM;
        for($i=0; $i < $this->length; $i++){
            $ascii = ord(substr($this->string, $i, 1));
            if( $ascii < 0x30 || $ascii > 0x39){
                if( ($ascii >= 0x41 && $ascii <= 0x5A) ||
                     $ascii == 0x20 ||
                     $ascii == 0x24 ||
                     $ascii == 0x25 ||
                     $ascii == 0x2A ||
                     $ascii == 0x2B ||
                     $ascii == 0x2D ||
                     $ascii == 0x2E ||
                     $ascii == 0x2F ||
                     $ascii == 0x3A){
                     $this->encode = EM_ALP;
                }else{
                    $this->encode = EM_BIN;
                    break;
                }
            }
        }
        $this->version = -1;
        for($i=1; $i<= MAX_VER; $i++){
            if(cQRCode::getCapacity($i, $this->errorLevel, 
            $this->encode)>=$this->length){
                $this->version = $i;
                $this->dataBits = cQRCode::getCapacity($i, $this->errorLevel, 
                DATA_BITS);
                break;
            }
        }
        if($this->version == -1){
            echo "String too long";
            exit();
        }
        switch ($this->version){
            case 2:
            case 3:
            case 4:
            case 5:
            case 6: $this->remBits = 7;
                break;
            case 14:
            case 15:
            case 16:
            case 17:
            case 18:
            case 19:
            case 20:
            case 28:
            case 29:
            case 30:
            case 31:
            case 32:
            case 33:
            case 34: $this->remBits = 3;
                break;
            case 21: 
            case 22:
            case 23:
            case 24:
            case 25:
            case 26:
            case 27: $this->remBits = 4;
                break;
            default: $this->remBits = 0;
                break;
        }
        if($debug){
            printf ("Version = %d <br>String Length= %d<br>Reminder Bits = %d<br>".
            "Error level (0=M, 1=L, 2=H, 3=Q)= %d<br>Encode Mode (1=Numeric, ".
            "2=Alphanumeric, 4=8-bit Byte, 8=Kanji)= %d<br> Capacity = %d <br>",
            $this->version, $this->length, $this->remBits, $this->errorlevel, 
            $this->encode, $this->capacity[$this->version][$this->errorLevel]);
        }
    }
    //convert the data characters into a binary string accordig to encode mode
    private function encodeStr(){
        if(DEBUG && (DEBUG_LEVEL & EQR_ENCODE)){
            $debug = true;
        }else{
            $debug = false;
        }
        if($this->version < 10){
            switch($this->encode){
                case EM_NUM: $this->bitsChar = 10;
                    break;
                case EM_ALP: $this->bitsChar = 9;
                    break;
                case EM_BIN: 
                case EM_JAP: $this->bitsChar = 8;
                    break;
            }
        }elseif($this->version <27){
            switch($this->encode){
                case EM_NUM: $this->bitsChar = 12;
                    break;
                case EM_ALP: $this->bitsChar = 11;
                    break;
                case EM_BIN: $this->bitsChar = 16;
                    break;
                case EM_JAP: $this->bitsChar = 10;
                    break;
            }
        }else{
            switch($this->encode){
                case EM_NUM: $this->bitsChar = 14;
                    break;
                case EM_ALP: $this->bitsChar = 13;
                    break;
                case EM_BIN: $this->bitsChar = 16;
                    break;
                case EM_JAP: $this->bitsChar = 12;
                    break;
            }
        }
        switch($this->encode){
                case EM_NUM: $this->encodeNum();
                    break;
                case EM_ALP: $this->encodeAlp();
                    break;
                case EM_BIN: $this->encodeBin();
                    break;
                case EM_JAP: $this->encodeJap();            
                    break;
        }
        //add terminator
        $this->strEncoded .= "0000";
        if(strlen($this->strEncoded) > $this->dataBits){
            $this->strEncoded = substr($this->strEncoded, 0, $this->dataBits);
        }
        if(strlen($this->strEncoded) < $this->dataBits){
            $residue = strlen($this->strEncoded) % CW_BITS;
            if($residue != 0){
                $this->strEncoded .= sprintf("%0".(CW_BITS - $residue)."b",0);
            }
        }
        //add Pad codewords
        while ( strlen($this->strEncoded) < $this->dataBits ){
            $this->strEncoded .= "11101100";
            if(strlen($this->strEncoded)< $this->dataBits ){
                $this->strEncoded .= "00010001";
            }
        }
        if($debug){
            printf("Number of bits in Character Count Indicator = %d<br>".
            "Encoded string : <br>%s<br>", $this->dataBits, 
            wordwrap($this->strEncoded, 64, "<br>", true));
        }
    }
    //encoding in numeric mode
    private function encodeNum(){
        $modeInd    = sprintf("%04b", $this->encode);
        $countInd   = sprintf("%0".$this->bitsChar."b", $this->length);
        $digits     = 3;
        $this->strEncoded = $modeInd.$countInd;
        $residue    = $this->length % $digits;
        for($i = 0; $i < $this->length - $residue; $i += $digits){
            $this->strEncoded .= sprintf("%010b", 
            substr($this->string, $i, $digits));
        }
        if($residue == 1){
            $this->strEncoded .= sprintf("%04b", substr($this->string, -1));
        }elseif($residue == 2){
            $this->strEncoded .= sprintf("%07b", substr($this->string, -2));
        }
    }
    //encoding in alphanumeric mode
    private function encodeAlp(){
        $val=0;
        for($i = 0x30; $i <= 0x39; $i++){
            $alphaTable[$i] = $val;
            $val++;
        }
        for($i = 0x41; $i <= 0x5A; $i++){
            $alphaTable[$i] = $val;
            $val++;
        }
        $alphaTable[0x20] = $val++;
        $alphaTable[0x24] = $val++;
        $alphaTable[0x25] = $val++;
        $alphaTable[0x2A] = $val++;
        $alphaTable[0x2B] = $val++;
        $alphaTable[0x2D] = $val++;
        $alphaTable[0x2E] = $val++;
        $alphaTable[0x2F] = $val++;
        $alphaTable[0x3A] = $val;
        $modeInd    = sprintf("%04b", $this->encode);
        $countInd   = sprintf("%0".$this->bitsChar."b", $this->length);
        $chars      = 2;
        $this->strEncoded = $modeInd.$countInd;
        $residue = $this->length % $chars;
        for($i = 0; $i < $this->length - $residue; $i += $chars){
            $pair = substr($this->string, $i, $chars);
            $sum  = $alphaTable[ord(substr($pair, 0, 1))] * 45 + 
            $alphaTable[ord(substr($pair, 1, 1))];
            $this->strEncoded .= sprintf("%011b", $sum);
        }
        if($residue != 0){
            $sum  = $alphaTable[ord(substr($this->string, -1))];
            $this->strEncoded .= sprintf("%06b", $sum);
        }
    }
    //encoding in byte mode
    private function encodeBin(){
        $modeInd    = sprintf("%04b", $this->encode);
        $countInd   = sprintf("%0".$this->bitsChar."b", $this->length);
        $this->strEncoded = $modeInd.$countInd;
        for($i = 0; $i < $this->length; $i++){
            $this->strEncoded .= sprintf("%0".CW_BITS."b", 
            ord(substr($this->string, $i, 1)));
        }
    
    }
    //auxiliar table to obtain number of blocks and sizes
    public static function getECTable($version, $errorLevel, $data){
        //Version 1
        $i = 1;
        $ecTable[$i][ECL_L][EC_CW]    = 7;
        $ecTable[$i][ECL_L][BLOCK1]   = 1;
        $ecTable[$i][ECL_L][BLOCK2]   = 0;
        $ecTable[$i][ECL_L][CW_BLCK1] = 26;
        $ecTable[$i][ECL_L][CW_BLCK2] = 0;
        $ecTable[$i][ECL_M][EC_CW]    = 10;
        $ecTable[$i][ECL_M][BLOCK1]   = 1;
        $ecTable[$i][ECL_M][BLOCK2]   = 0;
        $ecTable[$i][ECL_M][CW_BLCK1] = 26;
        $ecTable[$i][ECL_M][CW_BLCK2] = 0;
        $ecTable[$i][ECL_Q][EC_CW]    = 13;
        $ecTable[$i][ECL_Q][BLOCK1]   = 1;
        $ecTable[$i][ECL_Q][BLOCK2]   = 0;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 26;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 0;
        $ecTable[$i][ECL_H][EC_CW]    = 17;
        $ecTable[$i][ECL_H][BLOCK1]   = 1;
        $ecTable[$i][ECL_H][BLOCK2]   = 0;
        $ecTable[$i][ECL_H][CW_BLCK1] = 26;
        $ecTable[$i][ECL_H][CW_BLCK2] = 0;
      
        //Version 2
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 10;
        $ecTable[$i][ECL_L][BLOCK1]   = 1;
        $ecTable[$i][ECL_L][BLOCK2]   = 0;
        $ecTable[$i][ECL_L][CW_BLCK1] = 44;
        $ecTable[$i][ECL_L][CW_BLCK2] = 0;
        $ecTable[$i][ECL_M][EC_CW]    = 16;
        $ecTable[$i][ECL_M][BLOCK1]   = 1;
        $ecTable[$i][ECL_M][BLOCK2]   = 0;
        $ecTable[$i][ECL_M][CW_BLCK1] = 44;
        $ecTable[$i][ECL_M][CW_BLCK2] = 0;
        $ecTable[$i][ECL_Q][EC_CW]    = 22;
        $ecTable[$i][ECL_Q][BLOCK1]   = 1;
        $ecTable[$i][ECL_Q][BLOCK2]   = 0;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 44;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 0;
        $ecTable[$i][ECL_H][EC_CW]    = 28;
        $ecTable[$i][ECL_H][BLOCK1]   = 1;
        $ecTable[$i][ECL_H][BLOCK2]   = 0;
        $ecTable[$i][ECL_H][CW_BLCK1] = 44;
        $ecTable[$i][ECL_H][CW_BLCK2] = 0;

       //Version 3
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 15;
        $ecTable[$i][ECL_L][BLOCK1]   = 1;
        $ecTable[$i][ECL_L][BLOCK2]   = 0;
        $ecTable[$i][ECL_L][CW_BLCK1] = 70;
        $ecTable[$i][ECL_L][CW_BLCK2] = 0;
        $ecTable[$i][ECL_M][EC_CW]    = 26;
        $ecTable[$i][ECL_M][BLOCK1]   = 1;
        $ecTable[$i][ECL_M][BLOCK2]   = 0;
        $ecTable[$i][ECL_M][CW_BLCK1] = 70;
        $ecTable[$i][ECL_M][CW_BLCK2] = 0;
        $ecTable[$i][ECL_Q][EC_CW]    = 18;
        $ecTable[$i][ECL_Q][BLOCK1]   = 2;
        $ecTable[$i][ECL_Q][BLOCK2]   = 0;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 35;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 0;
        $ecTable[$i][ECL_H][EC_CW]    = 22;
        $ecTable[$i][ECL_H][BLOCK1]   = 2;
        $ecTable[$i][ECL_H][BLOCK2]   = 0;
        $ecTable[$i][ECL_H][CW_BLCK1] = 35;
        $ecTable[$i][ECL_H][CW_BLCK2] = 0;

        //Version 4
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 20;
        $ecTable[$i][ECL_L][BLOCK1]   = 1;
        $ecTable[$i][ECL_L][BLOCK2]   = 0;
        $ecTable[$i][ECL_L][CW_BLCK1] = 100;
        $ecTable[$i][ECL_L][CW_BLCK2] = 0;
        $ecTable[$i][ECL_M][EC_CW]    = 18;
        $ecTable[$i][ECL_M][BLOCK1]   = 2;
        $ecTable[$i][ECL_M][BLOCK2]   = 0;
        $ecTable[$i][ECL_M][CW_BLCK1] = 50;
        $ecTable[$i][ECL_M][CW_BLCK2] = 0;
        $ecTable[$i][ECL_Q][EC_CW]    = 26;
        $ecTable[$i][ECL_Q][BLOCK1]   = 2;
        $ecTable[$i][ECL_Q][BLOCK2]   = 0;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 50;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 0;
        $ecTable[$i][ECL_H][EC_CW]    = 16;
        $ecTable[$i][ECL_H][BLOCK1]   = 4;
        $ecTable[$i][ECL_H][BLOCK2]   = 0;
        $ecTable[$i][ECL_H][CW_BLCK1] = 25;
        $ecTable[$i][ECL_H][CW_BLCK2] = 0;
        
        //Version 5
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 26;
        $ecTable[$i][ECL_L][BLOCK1]   = 1;
        $ecTable[$i][ECL_L][BLOCK2]   = 0;
        $ecTable[$i][ECL_L][CW_BLCK1] = 134;
        $ecTable[$i][ECL_L][CW_BLCK2] = 0;
        $ecTable[$i][ECL_M][EC_CW]    = 24;
        $ecTable[$i][ECL_M][BLOCK1]   = 2;
        $ecTable[$i][ECL_M][BLOCK2]   = 0;
        $ecTable[$i][ECL_M][CW_BLCK1] = 67;
        $ecTable[$i][ECL_M][CW_BLCK2] = 0;
        $ecTable[$i][ECL_Q][EC_CW]    = 18;
        $ecTable[$i][ECL_Q][BLOCK1]   = 2;
        $ecTable[$i][ECL_Q][BLOCK2]   = 2;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 33;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 34;
        $ecTable[$i][ECL_H][EC_CW]    = 22;
        $ecTable[$i][ECL_H][BLOCK1]   = 2;
        $ecTable[$i][ECL_H][BLOCK2]   = 2;
        $ecTable[$i][ECL_H][CW_BLCK1] = 33;
        $ecTable[$i][ECL_H][CW_BLCK2] = 34;
        
        //Version 6
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 18;
        $ecTable[$i][ECL_L][BLOCK1]   = 2;
        $ecTable[$i][ECL_L][BLOCK2]   = 0;
        $ecTable[$i][ECL_L][CW_BLCK1] = 86;
        $ecTable[$i][ECL_L][CW_BLCK2] = 0;
        $ecTable[$i][ECL_M][EC_CW]    = 16;
        $ecTable[$i][ECL_M][BLOCK1]   = 4;
        $ecTable[$i][ECL_M][BLOCK2]   = 0;
        $ecTable[$i][ECL_M][CW_BLCK1] = 43;
        $ecTable[$i][ECL_M][CW_BLCK2] = 0;
        $ecTable[$i][ECL_Q][EC_CW]    = 24;
        $ecTable[$i][ECL_Q][BLOCK1]   = 4;
        $ecTable[$i][ECL_Q][BLOCK2]   = 0;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 43;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 0;
        $ecTable[$i][ECL_H][EC_CW]    = 28;
        $ecTable[$i][ECL_H][BLOCK1]   = 4;
        $ecTable[$i][ECL_H][BLOCK2]   = 0;
        $ecTable[$i][ECL_H][CW_BLCK1] = 43;
        $ecTable[$i][ECL_H][CW_BLCK2] = 0;
        
        //Version 7
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 20;
        $ecTable[$i][ECL_L][BLOCK1]   = 2;
        $ecTable[$i][ECL_L][BLOCK2]   = 0;
        $ecTable[$i][ECL_L][CW_BLCK1] = 98;
        $ecTable[$i][ECL_L][CW_BLCK2] = 0;
        $ecTable[$i][ECL_M][EC_CW]    = 18;
        $ecTable[$i][ECL_M][BLOCK1]   = 4;
        $ecTable[$i][ECL_M][BLOCK2]   = 0;
        $ecTable[$i][ECL_M][CW_BLCK1] = 49;
        $ecTable[$i][ECL_M][CW_BLCK2] = 0;
        $ecTable[$i][ECL_Q][EC_CW]    = 18;
        $ecTable[$i][ECL_Q][BLOCK1]   = 2;
        $ecTable[$i][ECL_Q][BLOCK2]   = 4;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 32;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 33;
        $ecTable[$i][ECL_H][EC_CW]    = 26;
        $ecTable[$i][ECL_H][BLOCK1]   = 4;
        $ecTable[$i][ECL_H][BLOCK2]   = 1;
        $ecTable[$i][ECL_H][CW_BLCK1] = 39;
        $ecTable[$i][ECL_H][CW_BLCK2] = 40;
        
        //Version 8
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 24;
        $ecTable[$i][ECL_L][BLOCK1]   = 2;
        $ecTable[$i][ECL_L][BLOCK2]   = 0;
        $ecTable[$i][ECL_L][CW_BLCK1] = 121;
        $ecTable[$i][ECL_L][CW_BLCK2] = 0;
        $ecTable[$i][ECL_M][EC_CW]    = 22;
        $ecTable[$i][ECL_M][BLOCK1]   = 2;
        $ecTable[$i][ECL_M][BLOCK2]   = 2;
        $ecTable[$i][ECL_M][CW_BLCK1] = 60;
        $ecTable[$i][ECL_M][CW_BLCK2] = 61;
        $ecTable[$i][ECL_Q][EC_CW]    = 22;
        $ecTable[$i][ECL_Q][BLOCK1]   = 4;
        $ecTable[$i][ECL_Q][BLOCK2]   = 2;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 40;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 41;
        $ecTable[$i][ECL_H][EC_CW]    = 26;
        $ecTable[$i][ECL_H][BLOCK1]   = 4;
        $ecTable[$i][ECL_H][BLOCK2]   = 2;
        $ecTable[$i][ECL_H][CW_BLCK1] = 40;
        $ecTable[$i][ECL_H][CW_BLCK2] = 41;
        
        //Version 9
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 2;
        $ecTable[$i][ECL_L][BLOCK2]   = 0;
        $ecTable[$i][ECL_L][CW_BLCK1] = 146;
        $ecTable[$i][ECL_L][CW_BLCK2] = 0;
        $ecTable[$i][ECL_M][EC_CW]    = 22;
        $ecTable[$i][ECL_M][BLOCK1]   = 3;
        $ecTable[$i][ECL_M][BLOCK2]   = 2;
        $ecTable[$i][ECL_M][CW_BLCK1] = 58;
        $ecTable[$i][ECL_M][CW_BLCK2] = 59;
        $ecTable[$i][ECL_Q][EC_CW]    = 20;
        $ecTable[$i][ECL_Q][BLOCK1]   = 4;
        $ecTable[$i][ECL_Q][BLOCK2]   = 4;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 36;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 37;
        $ecTable[$i][ECL_H][EC_CW]    = 24;
        $ecTable[$i][ECL_H][BLOCK1]   = 4;
        $ecTable[$i][ECL_H][BLOCK2]   = 4;
        $ecTable[$i][ECL_H][CW_BLCK1] = 36;
        $ecTable[$i][ECL_H][CW_BLCK2] = 37;
        
        //Version 10
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 18;
        $ecTable[$i][ECL_L][BLOCK1]   = 2;
        $ecTable[$i][ECL_L][BLOCK2]   = 2;
        $ecTable[$i][ECL_L][CW_BLCK1] = 86;
        $ecTable[$i][ECL_L][CW_BLCK2] = 87;
        $ecTable[$i][ECL_M][EC_CW]    = 26;
        $ecTable[$i][ECL_M][BLOCK1]   = 4;
        $ecTable[$i][ECL_M][BLOCK2]   = 1;
        $ecTable[$i][ECL_M][CW_BLCK1] = 69;
        $ecTable[$i][ECL_M][CW_BLCK2] = 70;
        $ecTable[$i][ECL_Q][EC_CW]    = 24;
        $ecTable[$i][ECL_Q][BLOCK1]   = 6;
        $ecTable[$i][ECL_Q][BLOCK2]   = 2;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 43;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 44;
        $ecTable[$i][ECL_H][EC_CW]    = 28;
        $ecTable[$i][ECL_H][BLOCK1]   = 6;
        $ecTable[$i][ECL_H][BLOCK2]   = 2;
        $ecTable[$i][ECL_H][CW_BLCK1] = 43;
        $ecTable[$i][ECL_H][CW_BLCK2] = 44;
        
        //Version 11
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 20;
        $ecTable[$i][ECL_L][BLOCK1]   = 4;
        $ecTable[$i][ECL_L][BLOCK2]   = 0;
        $ecTable[$i][ECL_L][CW_BLCK1] = 101;
        $ecTable[$i][ECL_L][CW_BLCK2] = 0;
        $ecTable[$i][ECL_M][EC_CW]    = 30;
        $ecTable[$i][ECL_M][BLOCK1]   = 1;
        $ecTable[$i][ECL_M][BLOCK2]   = 80;
        $ecTable[$i][ECL_M][CW_BLCK1] = 81;
        $ecTable[$i][ECL_M][CW_BLCK2] = 4;
        $ecTable[$i][ECL_Q][EC_CW]    = 28;
        $ecTable[$i][ECL_Q][BLOCK1]   = 4;
        $ecTable[$i][ECL_Q][BLOCK2]   = 4;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 50;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 51;
        $ecTable[$i][ECL_H][EC_CW]    = 24;
        $ecTable[$i][ECL_H][BLOCK1]   = 3;
        $ecTable[$i][ECL_H][BLOCK2]   = 8;
        $ecTable[$i][ECL_H][CW_BLCK1] = 36;
        $ecTable[$i][ECL_H][CW_BLCK2] = 37;
        
        //Version 12
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 24;
        $ecTable[$i][ECL_L][BLOCK1]   = 2;
        $ecTable[$i][ECL_L][BLOCK2]   = 2;
        $ecTable[$i][ECL_L][CW_BLCK1] = 116;
        $ecTable[$i][ECL_L][CW_BLCK2] = 117;
        $ecTable[$i][ECL_M][EC_CW]    = 22;
        $ecTable[$i][ECL_M][BLOCK1]   = 6;
        $ecTable[$i][ECL_M][BLOCK2]   = 2;
        $ecTable[$i][ECL_M][CW_BLCK1] = 58;
        $ecTable[$i][ECL_M][CW_BLCK2] = 59;
        $ecTable[$i][ECL_Q][EC_CW]    = 26;
        $ecTable[$i][ECL_Q][BLOCK1]   = 4;
        $ecTable[$i][ECL_Q][BLOCK2]   = 6;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 46;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 47;
        $ecTable[$i][ECL_H][EC_CW]    = 28;
        $ecTable[$i][ECL_H][BLOCK1]   = 7;
        $ecTable[$i][ECL_H][BLOCK2]   = 4;
        $ecTable[$i][ECL_H][CW_BLCK1] = 42;
        $ecTable[$i][ECL_H][CW_BLCK2] = 43;
        
        //Version 13
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 26;
        $ecTable[$i][ECL_L][BLOCK1]   = 4;
        $ecTable[$i][ECL_L][BLOCK2]   = 0;
        $ecTable[$i][ECL_L][CW_BLCK1] = 133;
        $ecTable[$i][ECL_L][CW_BLCK2] = 0;
        $ecTable[$i][ECL_M][EC_CW]    = 22;
        $ecTable[$i][ECL_M][BLOCK1]   = 8;
        $ecTable[$i][ECL_M][BLOCK2]   = 1;
        $ecTable[$i][ECL_M][CW_BLCK1] = 59;
        $ecTable[$i][ECL_M][CW_BLCK2] = 60;
        $ecTable[$i][ECL_Q][EC_CW]    = 24;
        $ecTable[$i][ECL_Q][BLOCK1]   = 8;
        $ecTable[$i][ECL_Q][BLOCK2]   = 4;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 44;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 45;
        $ecTable[$i][ECL_H][EC_CW]    = 22;
        $ecTable[$i][ECL_H][BLOCK1]   = 12;
        $ecTable[$i][ECL_H][BLOCK2]   = 4;
        $ecTable[$i][ECL_H][CW_BLCK1] = 33;
        $ecTable[$i][ECL_H][CW_BLCK2] = 34;
        
        //Version 14
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 3;
        $ecTable[$i][ECL_L][BLOCK2]   = 1;
        $ecTable[$i][ECL_L][CW_BLCK1] = 145;
        $ecTable[$i][ECL_L][CW_BLCK2] = 146;
        $ecTable[$i][ECL_M][EC_CW]    = 24;
        $ecTable[$i][ECL_M][BLOCK1]   = 4;
        $ecTable[$i][ECL_M][BLOCK2]   = 5;
        $ecTable[$i][ECL_M][CW_BLCK1] = 64;
        $ecTable[$i][ECL_M][CW_BLCK2] = 65;
        $ecTable[$i][ECL_Q][EC_CW]    = 20;
        $ecTable[$i][ECL_Q][BLOCK1]   = 11;
        $ecTable[$i][ECL_Q][BLOCK2]   = 5;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 36;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 37;
        $ecTable[$i][ECL_H][EC_CW]    = 24;
        $ecTable[$i][ECL_H][BLOCK1]   = 11;
        $ecTable[$i][ECL_H][BLOCK2]   = 5;
        $ecTable[$i][ECL_H][CW_BLCK1] = 36;
        $ecTable[$i][ECL_H][CW_BLCK2] = 37;
        
        //Version 15
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 22;
        $ecTable[$i][ECL_L][BLOCK1]   = 5;
        $ecTable[$i][ECL_L][BLOCK2]   = 1;
        $ecTable[$i][ECL_L][CW_BLCK1] = 109;
        $ecTable[$i][ECL_L][CW_BLCK2] = 110;
        $ecTable[$i][ECL_M][EC_CW]    = 24;
        $ecTable[$i][ECL_M][BLOCK1]   = 5;
        $ecTable[$i][ECL_M][BLOCK2]   = 5;
        $ecTable[$i][ECL_M][CW_BLCK1] = 65;
        $ecTable[$i][ECL_M][CW_BLCK2] = 66;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 5;
        $ecTable[$i][ECL_Q][BLOCK2]   = 7;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 24;
        $ecTable[$i][ECL_H][BLOCK1]   = 11;
        $ecTable[$i][ECL_H][BLOCK2]   = 7;
        $ecTable[$i][ECL_H][CW_BLCK1] = 36;
        $ecTable[$i][ECL_H][CW_BLCK2] = 37;
        
        //Version 16
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 24;
        $ecTable[$i][ECL_L][BLOCK1]   = 5;
        $ecTable[$i][ECL_L][BLOCK2]   = 1;
        $ecTable[$i][ECL_L][CW_BLCK1] = 122;
        $ecTable[$i][ECL_L][CW_BLCK2] = 123;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 7;
        $ecTable[$i][ECL_M][BLOCK2]   = 3;
        $ecTable[$i][ECL_M][CW_BLCK1] = 73;
        $ecTable[$i][ECL_M][CW_BLCK2] = 74;
        $ecTable[$i][ECL_Q][EC_CW]    = 24;
        $ecTable[$i][ECL_Q][BLOCK1]   = 15;
        $ecTable[$i][ECL_Q][BLOCK2]   = 2;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 43;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 44;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 3;
        $ecTable[$i][ECL_H][BLOCK2]   = 13;
        $ecTable[$i][ECL_H][CW_BLCK1] = 45;
        $ecTable[$i][ECL_H][CW_BLCK2] = 46;
        
        //Version 17
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 28;
        $ecTable[$i][ECL_L][BLOCK1]   = 1;
        $ecTable[$i][ECL_L][BLOCK2]   = 5;
        $ecTable[$i][ECL_L][CW_BLCK1] = 135;
        $ecTable[$i][ECL_L][CW_BLCK2] = 136;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 10;
        $ecTable[$i][ECL_M][BLOCK2]   = 1;
        $ecTable[$i][ECL_M][CW_BLCK1] = 74;
        $ecTable[$i][ECL_M][CW_BLCK2] = 75;
        $ecTable[$i][ECL_Q][EC_CW]    = 28;
        $ecTable[$i][ECL_Q][BLOCK1]   = 1;
        $ecTable[$i][ECL_Q][BLOCK2]   = 15;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 50;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 51;
        $ecTable[$i][ECL_H][EC_CW]    = 28;
        $ecTable[$i][ECL_H][BLOCK1]   = 2;
        $ecTable[$i][ECL_H][BLOCK2]   = 17;
        $ecTable[$i][ECL_H][CW_BLCK1] = 42;
        $ecTable[$i][ECL_H][CW_BLCK2] = 43;
        
        //Version 18
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 5;
        $ecTable[$i][ECL_L][BLOCK2]   = 1;
        $ecTable[$i][ECL_L][CW_BLCK1] = 150;
        $ecTable[$i][ECL_L][CW_BLCK2] = 151;
        $ecTable[$i][ECL_M][EC_CW]    = 26;
        $ecTable[$i][ECL_M][BLOCK1]   = 9;
        $ecTable[$i][ECL_M][BLOCK2]   = 4;
        $ecTable[$i][ECL_M][CW_BLCK1] = 69;
        $ecTable[$i][ECL_M][CW_BLCK2] = 70;
        $ecTable[$i][ECL_Q][EC_CW]    = 28;
        $ecTable[$i][ECL_Q][BLOCK1]   = 17;
        $ecTable[$i][ECL_Q][BLOCK2]   = 1;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 50;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 51;
        $ecTable[$i][ECL_H][EC_CW]    = 28;
        $ecTable[$i][ECL_H][BLOCK1]   = 2;
        $ecTable[$i][ECL_H][BLOCK2]   = 19;
        $ecTable[$i][ECL_H][CW_BLCK1] = 42;
        $ecTable[$i][ECL_H][CW_BLCK2] = 43;
        
        //Version 19
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 28;
        $ecTable[$i][ECL_L][BLOCK1]   = 3;
        $ecTable[$i][ECL_L][BLOCK2]   = 4;
        $ecTable[$i][ECL_L][CW_BLCK1] = 141;
        $ecTable[$i][ECL_L][CW_BLCK2] = 142;
        $ecTable[$i][ECL_M][EC_CW]    = 26;
        $ecTable[$i][ECL_M][BLOCK1]   = 3;
        $ecTable[$i][ECL_M][BLOCK2]   = 11;
        $ecTable[$i][ECL_M][CW_BLCK1] = 70;
        $ecTable[$i][ECL_M][CW_BLCK2] = 71;
        $ecTable[$i][ECL_Q][EC_CW]    = 26;
        $ecTable[$i][ECL_Q][BLOCK1]   = 17;
        $ecTable[$i][ECL_Q][BLOCK2]   = 4;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 47;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 48;
        $ecTable[$i][ECL_H][EC_CW]    = 26;
        $ecTable[$i][ECL_H][BLOCK1]   = 9;
        $ecTable[$i][ECL_H][BLOCK2]   = 16;
        $ecTable[$i][ECL_H][CW_BLCK1] = 39;
        $ecTable[$i][ECL_H][CW_BLCK2] = 40;
        
        //Version 20
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 28;
        $ecTable[$i][ECL_L][BLOCK1]   = 3;
        $ecTable[$i][ECL_L][BLOCK2]   = 5;
        $ecTable[$i][ECL_L][CW_BLCK1] = 135;
        $ecTable[$i][ECL_L][CW_BLCK2] = 136;
        $ecTable[$i][ECL_M][EC_CW]    = 26;
        $ecTable[$i][ECL_M][BLOCK1]   = 3;
        $ecTable[$i][ECL_M][BLOCK2]   = 13;
        $ecTable[$i][ECL_M][CW_BLCK1] = 67;
        $ecTable[$i][ECL_M][CW_BLCK2] = 68;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 15;
        $ecTable[$i][ECL_Q][BLOCK2]   = 5;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 28;
        $ecTable[$i][ECL_H][BLOCK1]   = 15;
        $ecTable[$i][ECL_H][BLOCK2]   = 10;
        $ecTable[$i][ECL_H][CW_BLCK1] = 43;
        $ecTable[$i][ECL_H][CW_BLCK2] = 44;
        
        //Version 21
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 28;
        $ecTable[$i][ECL_L][BLOCK1]   = 4;
        $ecTable[$i][ECL_L][BLOCK2]   = 4;
        $ecTable[$i][ECL_L][CW_BLCK1] = 144;
        $ecTable[$i][ECL_L][CW_BLCK2] = 145;
        $ecTable[$i][ECL_M][EC_CW]    = 26;
        $ecTable[$i][ECL_M][BLOCK1]   = 17;
        $ecTable[$i][ECL_M][BLOCK2]   = 0;
        $ecTable[$i][ECL_M][CW_BLCK1] = 68;
        $ecTable[$i][ECL_M][CW_BLCK2] = 0;
        $ecTable[$i][ECL_Q][EC_CW]    = 28;
        $ecTable[$i][ECL_Q][BLOCK1]   = 17;
        $ecTable[$i][ECL_Q][BLOCK2]   = 6;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 50;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 51;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 19;
        $ecTable[$i][ECL_H][BLOCK2]   = 6;
        $ecTable[$i][ECL_H][CW_BLCK1] = 46;
        $ecTable[$i][ECL_H][CW_BLCK2] = 47;
        
        //Version 22
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 28;
        $ecTable[$i][ECL_L][BLOCK1]   = 2;
        $ecTable[$i][ECL_L][BLOCK2]   = 7;
        $ecTable[$i][ECL_L][CW_BLCK1] = 139;
        $ecTable[$i][ECL_L][CW_BLCK2] = 140;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 17;
        $ecTable[$i][ECL_M][BLOCK2]   = 0;
        $ecTable[$i][ECL_M][CW_BLCK1] = 74;
        $ecTable[$i][ECL_M][CW_BLCK2] = 0;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 7;
        $ecTable[$i][ECL_Q][BLOCK2]   = 16;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 24;
        $ecTable[$i][ECL_H][BLOCK1]   = 34;
        $ecTable[$i][ECL_H][BLOCK2]   = 0;
        $ecTable[$i][ECL_H][CW_BLCK1] = 37;
        $ecTable[$i][ECL_H][CW_BLCK2] = 0;
        
        //Version 23
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 4;
        $ecTable[$i][ECL_L][BLOCK2]   = 5;
        $ecTable[$i][ECL_L][CW_BLCK1] = 151;
        $ecTable[$i][ECL_L][CW_BLCK2] = 152;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 4;
        $ecTable[$i][ECL_M][BLOCK2]   = 14;
        $ecTable[$i][ECL_M][CW_BLCK1] = 75;
        $ecTable[$i][ECL_M][CW_BLCK2] = 76;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 11;
        $ecTable[$i][ECL_Q][BLOCK2]   = 14;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 16;
        $ecTable[$i][ECL_H][BLOCK2]   = 14;
        $ecTable[$i][ECL_H][CW_BLCK1] = 45;
        $ecTable[$i][ECL_H][CW_BLCK2] = 46;
        
        //Version 24
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 6;
        $ecTable[$i][ECL_L][BLOCK2]   = 4;
        $ecTable[$i][ECL_L][CW_BLCK1] = 147;
        $ecTable[$i][ECL_L][CW_BLCK2] = 148;
        $ecTable[$i][ECL_M][EC_CW]    = 30;
        $ecTable[$i][ECL_M][BLOCK1]   = 6;
        $ecTable[$i][ECL_M][BLOCK2]   = 14;
        $ecTable[$i][ECL_M][CW_BLCK1] = 73;
        $ecTable[$i][ECL_M][CW_BLCK2] = 74;
        $ecTable[$i][ECL_Q][EC_CW]    = 28;
        $ecTable[$i][ECL_Q][BLOCK1]   = 11;
        $ecTable[$i][ECL_Q][BLOCK2]   = 16;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 30;
        $ecTable[$i][ECL_H][BLOCK2]   = 2;
        $ecTable[$i][ECL_H][CW_BLCK1] = 46;
        $ecTable[$i][ECL_H][CW_BLCK2] = 47;
        
        //Version 25
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 26;
        $ecTable[$i][ECL_L][BLOCK1]   = 8;
        $ecTable[$i][ECL_L][BLOCK2]   = 4;
        $ecTable[$i][ECL_L][CW_BLCK1] = 132;
        $ecTable[$i][ECL_L][CW_BLCK2] = 133;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 8;
        $ecTable[$i][ECL_M][BLOCK2]   = 13;
        $ecTable[$i][ECL_M][CW_BLCK1] = 75;
        $ecTable[$i][ECL_M][CW_BLCK2] = 76;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 7;
        $ecTable[$i][ECL_Q][BLOCK2]   = 22;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 22;
        $ecTable[$i][ECL_H][BLOCK2]   = 13;
        $ecTable[$i][ECL_H][CW_BLCK1] = 45;
        $ecTable[$i][ECL_H][CW_BLCK2] = 46;
        
        //Version 26
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 28;
        $ecTable[$i][ECL_L][BLOCK1]   = 10;
        $ecTable[$i][ECL_L][BLOCK2]   = 2;
        $ecTable[$i][ECL_L][CW_BLCK1] = 142;
        $ecTable[$i][ECL_L][CW_BLCK2] = 143;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 19;
        $ecTable[$i][ECL_M][BLOCK2]   = 4;
        $ecTable[$i][ECL_M][CW_BLCK1] = 74;
        $ecTable[$i][ECL_M][CW_BLCK2] = 75;
        $ecTable[$i][ECL_Q][EC_CW]    = 28;
        $ecTable[$i][ECL_Q][BLOCK1]   = 28;
        $ecTable[$i][ECL_Q][BLOCK2]   = 6;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 50;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 51;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 33;
        $ecTable[$i][ECL_H][BLOCK2]   = 4;
        $ecTable[$i][ECL_H][CW_BLCK1] = 46;
        $ecTable[$i][ECL_H][CW_BLCK2] = 47;
        
        //Version 27
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 8;
        $ecTable[$i][ECL_L][BLOCK2]   = 4;
        $ecTable[$i][ECL_L][CW_BLCK1] = 152;
        $ecTable[$i][ECL_L][CW_BLCK2] = 153;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 22;
        $ecTable[$i][ECL_M][BLOCK2]   = 3;
        $ecTable[$i][ECL_M][CW_BLCK1] = 73;
        $ecTable[$i][ECL_M][CW_BLCK2] = 74;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 8;
        $ecTable[$i][ECL_Q][BLOCK2]   = 26;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 53;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 54;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 12;
        $ecTable[$i][ECL_H][BLOCK2]   = 28;
        $ecTable[$i][ECL_H][CW_BLCK1] = 45;
        $ecTable[$i][ECL_H][CW_BLCK2] = 46;
        
        //Version 28
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 3;
        $ecTable[$i][ECL_L][BLOCK2]   = 10;
        $ecTable[$i][ECL_L][CW_BLCK1] = 147;
        $ecTable[$i][ECL_L][CW_BLCK2] = 148;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 3;
        $ecTable[$i][ECL_M][BLOCK2]   = 23;
        $ecTable[$i][ECL_M][CW_BLCK1] = 73;
        $ecTable[$i][ECL_M][CW_BLCK2] = 74;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 4;
        $ecTable[$i][ECL_Q][BLOCK2]   = 31;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 11;
        $ecTable[$i][ECL_H][BLOCK2]   = 31;
        $ecTable[$i][ECL_H][CW_BLCK1] = 45;
        $ecTable[$i][ECL_H][CW_BLCK2] = 46;
        
        //Version 29
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 7;
        $ecTable[$i][ECL_L][BLOCK2]   = 7;
        $ecTable[$i][ECL_L][CW_BLCK1] = 146;
        $ecTable[$i][ECL_L][CW_BLCK2] = 147;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 21;
        $ecTable[$i][ECL_M][BLOCK2]   = 7;
        $ecTable[$i][ECL_M][CW_BLCK1] = 73;
        $ecTable[$i][ECL_M][CW_BLCK2] = 74;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 1;
        $ecTable[$i][ECL_Q][BLOCK2]   = 37;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 53;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 54;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 19;
        $ecTable[$i][ECL_H][BLOCK2]   = 26;
        $ecTable[$i][ECL_H][CW_BLCK1] = 45;
        $ecTable[$i][ECL_H][CW_BLCK2] = 46;
        
        //Version 30
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 5;
        $ecTable[$i][ECL_L][BLOCK2]   = 10;
        $ecTable[$i][ECL_L][CW_BLCK1] = 145;
        $ecTable[$i][ECL_L][CW_BLCK2] = 146;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 19;
        $ecTable[$i][ECL_M][BLOCK2]   = 10;
        $ecTable[$i][ECL_M][CW_BLCK1] = 75;
        $ecTable[$i][ECL_M][CW_BLCK2] = 76;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 15;
        $ecTable[$i][ECL_Q][BLOCK2]   = 25;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 23;
        $ecTable[$i][ECL_H][BLOCK2]   = 25;
        $ecTable[$i][ECL_H][CW_BLCK1] = 45;
        $ecTable[$i][ECL_H][CW_BLCK2] = 46;
        
        //Version 31
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 13;
        $ecTable[$i][ECL_L][BLOCK2]   = 3;
        $ecTable[$i][ECL_L][CW_BLCK1] = 145;
        $ecTable[$i][ECL_L][CW_BLCK2] = 146;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 2;
        $ecTable[$i][ECL_M][BLOCK2]   = 29;
        $ecTable[$i][ECL_M][CW_BLCK1] = 74;
        $ecTable[$i][ECL_M][CW_BLCK2] = 75;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 42;
        $ecTable[$i][ECL_Q][BLOCK2]   = 1;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 23;
        $ecTable[$i][ECL_H][BLOCK2]   = 28;
        $ecTable[$i][ECL_H][CW_BLCK1] = 45;
        $ecTable[$i][ECL_H][CW_BLCK2] = 46;
        
        //Version 32
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 17;
        $ecTable[$i][ECL_L][BLOCK2]   = 0;
        $ecTable[$i][ECL_L][CW_BLCK1] = 145;
        $ecTable[$i][ECL_L][CW_BLCK2] = 0;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 10;
        $ecTable[$i][ECL_M][BLOCK2]   = 23;
        $ecTable[$i][ECL_M][CW_BLCK1] = 74;
        $ecTable[$i][ECL_M][CW_BLCK2] = 75;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 10;
        $ecTable[$i][ECL_Q][BLOCK2]   = 35;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 19;
        $ecTable[$i][ECL_H][BLOCK2]   = 35;
        $ecTable[$i][ECL_H][CW_BLCK1] = 45;
        $ecTable[$i][ECL_H][CW_BLCK2] = 46;
        
        //Version 33
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 17;
        $ecTable[$i][ECL_L][BLOCK2]   = 1;
        $ecTable[$i][ECL_L][CW_BLCK1] = 145;
        $ecTable[$i][ECL_L][CW_BLCK2] = 146;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 14;
        $ecTable[$i][ECL_M][BLOCK2]   = 21;
        $ecTable[$i][ECL_M][CW_BLCK1] = 74;
        $ecTable[$i][ECL_M][CW_BLCK2] = 75;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 29;
        $ecTable[$i][ECL_Q][BLOCK2]   = 19;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 11;
        $ecTable[$i][ECL_H][BLOCK2]   = 46;
        $ecTable[$i][ECL_H][CW_BLCK1] = 45;
        $ecTable[$i][ECL_H][CW_BLCK2] = 46;
        
        //Version 34
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 13;
        $ecTable[$i][ECL_L][BLOCK2]   = 6;
        $ecTable[$i][ECL_L][CW_BLCK1] = 145;
        $ecTable[$i][ECL_L][CW_BLCK2] = 146;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 14;
        $ecTable[$i][ECL_M][BLOCK2]   = 23;
        $ecTable[$i][ECL_M][CW_BLCK1] = 74;
        $ecTable[$i][ECL_M][CW_BLCK2] = 75;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 44;
        $ecTable[$i][ECL_Q][BLOCK2]   = 7;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 59;
        $ecTable[$i][ECL_H][BLOCK2]   = 1;
        $ecTable[$i][ECL_H][CW_BLCK1] = 46;
        $ecTable[$i][ECL_H][CW_BLCK2] = 47;
        
        //Version 35
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 12;
        $ecTable[$i][ECL_L][BLOCK2]   = 7;
        $ecTable[$i][ECL_L][CW_BLCK1] = 151;
        $ecTable[$i][ECL_L][CW_BLCK2] = 152;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 12;
        $ecTable[$i][ECL_M][BLOCK2]   = 26;
        $ecTable[$i][ECL_M][CW_BLCK1] = 75;
        $ecTable[$i][ECL_M][CW_BLCK2] = 76;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 39;
        $ecTable[$i][ECL_Q][BLOCK2]   = 14;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 22;
        $ecTable[$i][ECL_H][BLOCK2]   = 41;
        $ecTable[$i][ECL_H][CW_BLCK1] = 45;
        $ecTable[$i][ECL_H][CW_BLCK2] = 46;
        
        //Version 36
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 6;
        $ecTable[$i][ECL_L][BLOCK2]   = 14;
        $ecTable[$i][ECL_L][CW_BLCK1] = 151;
        $ecTable[$i][ECL_L][CW_BLCK2] = 152;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 6;
        $ecTable[$i][ECL_M][BLOCK2]   = 34;
        $ecTable[$i][ECL_M][CW_BLCK1] = 75;
        $ecTable[$i][ECL_M][CW_BLCK2] = 76;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 46;
        $ecTable[$i][ECL_Q][BLOCK2]   = 10;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 2;
        $ecTable[$i][ECL_H][BLOCK2]   = 64;
        $ecTable[$i][ECL_H][CW_BLCK1] = 45;
        $ecTable[$i][ECL_H][CW_BLCK2] = 46;
        
        //Version 37
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 17;
        $ecTable[$i][ECL_L][BLOCK2]   = 4;
        $ecTable[$i][ECL_L][CW_BLCK1] = 152;
        $ecTable[$i][ECL_L][CW_BLCK2] = 153;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 29;
        $ecTable[$i][ECL_M][BLOCK2]   = 14;
        $ecTable[$i][ECL_M][CW_BLCK1] = 74;
        $ecTable[$i][ECL_M][CW_BLCK2] = 75;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 49;
        $ecTable[$i][ECL_Q][BLOCK2]   = 10;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 24;
        $ecTable[$i][ECL_H][BLOCK2]   = 46;
        $ecTable[$i][ECL_H][CW_BLCK1] = 45;
        $ecTable[$i][ECL_H][CW_BLCK2] = 46;
        
        //Version 38
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 4;
        $ecTable[$i][ECL_L][BLOCK2]   = 18;
        $ecTable[$i][ECL_L][CW_BLCK1] = 152;
        $ecTable[$i][ECL_L][CW_BLCK2] = 153;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 13;
        $ecTable[$i][ECL_M][BLOCK2]   = 32;
        $ecTable[$i][ECL_M][CW_BLCK1] = 74;
        $ecTable[$i][ECL_M][CW_BLCK2] = 75;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 48;
        $ecTable[$i][ECL_Q][BLOCK2]   = 14;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 42;
        $ecTable[$i][ECL_H][BLOCK2]   = 32;
        $ecTable[$i][ECL_H][CW_BLCK1] = 45;
        $ecTable[$i][ECL_H][CW_BLCK2] = 46;
        
        //Version 39
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 20;
        $ecTable[$i][ECL_L][BLOCK2]   = 4;
        $ecTable[$i][ECL_L][CW_BLCK1] = 147;
        $ecTable[$i][ECL_L][CW_BLCK2] = 148;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 40;
        $ecTable[$i][ECL_M][BLOCK2]   = 7;
        $ecTable[$i][ECL_M][CW_BLCK1] = 75;
        $ecTable[$i][ECL_M][CW_BLCK2] = 76;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 43;
        $ecTable[$i][ECL_Q][BLOCK2]   = 22;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 10;
        $ecTable[$i][ECL_H][BLOCK2]   = 67;
        $ecTable[$i][ECL_H][CW_BLCK1] = 45;
        $ecTable[$i][ECL_H][CW_BLCK2] = 46;
        
        //Version 40
        $i++;
        $ecTable[$i][ECL_L][EC_CW]    = 30;
        $ecTable[$i][ECL_L][BLOCK1]   = 19;
        $ecTable[$i][ECL_L][BLOCK2]   = 6;
        $ecTable[$i][ECL_L][CW_BLCK1] = 148;
        $ecTable[$i][ECL_L][CW_BLCK2] = 149;
        $ecTable[$i][ECL_M][EC_CW]    = 28;
        $ecTable[$i][ECL_M][BLOCK1]   = 18;
        $ecTable[$i][ECL_M][BLOCK2]   = 31;
        $ecTable[$i][ECL_M][CW_BLCK1] = 75;
        $ecTable[$i][ECL_M][CW_BLCK2] = 76;
        $ecTable[$i][ECL_Q][EC_CW]    = 30;
        $ecTable[$i][ECL_Q][BLOCK1]   = 34;
        $ecTable[$i][ECL_Q][BLOCK2]   = 34;
        $ecTable[$i][ECL_Q][CW_BLCK1] = 54;
        $ecTable[$i][ECL_Q][CW_BLCK2] = 55;
        $ecTable[$i][ECL_H][EC_CW]    = 30;
        $ecTable[$i][ECL_H][BLOCK1]   = 20;
        $ecTable[$i][ECL_H][BLOCK2]   = 61;
        $ecTable[$i][ECL_H][CW_BLCK1] = 45;
        $ecTable[$i][ECL_H][CW_BLCK2] = 46;
        
        return $ecTable[$version][$errorLevel][$data];
    }
    //Generate de error correction codewords for each block
    private function errorCorrection(){
        if(DEBUG && (DEBUG_LEVEL & EQR_EC)){
                $debug = true;
        }else{
            $debug = false;
        }
        //Generator polynomial for Reed-salomon error correction codewords
        $ecPolynomial[7]  = array(0, 87, 229, 146, 149, 238, 102, 21);
        $ecPolynomial[10] = array(0, 251, 67, 46, 61, 118, 70, 64, 94, 32, 45);
        $ecPolynomial[13] = array(0, 74, 152, 176, 100, 86, 100, 106, 104, 130,
                            218, 206, 140, 78);
        $ecPolynomial[15] = array(0, 8, 183, 61, 91, 202, 37, 51, 58, 58, 237, 
                            140, 124, 5, 99, 105);
        $ecPolynomial[16] = array(0, 120, 104, 107, 109, 102, 161, 76, 3, 91, 
                            191, 147, 169, 182, 194, 255, 120);
        $ecPolynomial[17] = array(0, 43, 139, 206, 78, 43, 239, 123, 206, 214,
                            147, 24, 99, 150, 39, 243, 163, 136);
        $ecPolynomial[18] = array(0, 215, 234, 158, 94, 184, 97, 118, 170, 79,
                            187, 152, 148, 252, 179, 5, 98, 96, 153);
        $ecPolynomial[20] = array(0, 17, 60, 79, 50, 61, 163, 26, 187, 202, 180, 
                            221, 225, 83, 239, 156, 164, 212, 212, 188, 190);
        $ecPolynomial[22] = array(0, 210, 171, 247, 242, 93, 230, 14, 109, 221,
                            53, 200, 74, 8, 172, 98, 80, 219, 134, 160, 105, 
                            165, 231);
        $ecPolynomial[24] = array(0, 229, 121, 135, 48, 211, 117, 251, 126, 159,
                            180, 169, 152, 192, 226, 228, 218, 111, 0, 117, 232,
                            87, 96, 227, 21);
        $ecPolynomial[26] = array(0, 173, 125, 158, 2, 103, 182, 118, 17, 145, 
                            201, 111, 28, 165, 53, 161, 21, 245, 142, 13, 102,
                            48, 227, 153, 145, 218, 70);
        $ecPolynomial[28] = array(0, 168, 223, 200, 104, 224, 234, 108, 180, 110,
                            190, 195, 147, 205, 27, 232, 201, 21, 43, 245, 87, 42,
                            195, 212, 119, 242, 37, 9, 123);
        $ecPolynomial[30] = array(0, 41, 173, 145, 152, 216, 31, 179, 182, 50, 48,
                            110, 86, 239, 96, 222, 125, 42, 173, 226, 193, 224, 130,
                            156, 37, 251, 216, 238, 40, 192, 180);   

        $this->ecCw     = cQRCode::getECTable($this->version,$this->errorLevel,EC_CW);
        $this->genPoly  = $ecPolynomial[$this->ecCw];
        $this->initExp1 = cQRCode::getECTable($this->version,$this->errorLevel,CW_BLCK1) - 1;
        $this->initExp2 = cQRCode::getECTable($this->version,$this->errorLevel,CW_BLCK2) - 1;
        $this->ecBlck1  = cQRCode::getECTable($this->version,$this->errorLevel,BLOCK1);
        $this->ecBlck2  = cQRCode::getECTable($this->version,$this->errorLevel,BLOCK2);
        $index=0;
        if($debug){
            printf("Error Correction Codewords %d<br>Block 1 Number of Error ".
            "Correction Blocks %d<br>Block 2 Number of Error Correction Blocks %d<br>",
            $this->ecCw,$this->ecBlck1,$this->ecBlck2);
        }
        $data1Len = cQRCode::getECTable($this->version, $this->errorLevel, 
        CW_BLCK1) - $this->ecCw;
        for($i = 1; $i <=  $this->ecBlck1; $i++ ){
            $dataBlck1[$i] = substr ($this->strEncoded, $index, CW_BITS * 
            $data1Len );
            if (isset($mssgPoly)) unset($mssgPoly[$i]);
            $mssgPoly[$i] = new cIPoly();
            if (isset($ecPoly1)) unset($ecPoly1[$i]);
            $ecPoly1[$i]  = new cAPoly();
            //generate ecc polynomial
            for($k = 0; $k < count( $ecPolynomial[$this->ecCw ]); $k++){
                $ecPoly1[$i]->addTerm(new cATerm($ecPolynomial[$this->ecCw ][$k], 
                $this->initExp1 - $k));
            }
            $exp = $this->initExp1;
            //generate message polynomial
            for($j = 0; $j < CW_BITS * $data1Len; $j += CW_BITS){
                $coeff = bindec(substr($dataBlck1[$i], $j, CW_BITS));
                $mssgPoly[$i]->addTerm(new cITerm($coeff, $exp));
                $exp--;
            }
            if($debug){
                print "Generator polynomial (Block 1) ".$ecPoly1[$i]."<br>";
                printf("Data Block 1, %d : <br>%s<br>Message polynomial= <br>%s<br>",
                $i, wordwrap($dataBlck1[$i], 64, "<br>", true), $mssgPoly[$i]);    
            }
            $ecBlck1[$i] = $this->computeEC($ecPoly1[$i],$mssgPoly[$i]);
            if($debug){
                printf("Error Correction Block 1, %d : <br>%s<br>", $i,
                wordwrap($ecBlck1[$i], 64, "<br>", true));
            }
            $index += CW_BITS * $data1Len;
        }
        $data2Len = cQRCode::getECTable($this->version, $this->errorLevel,
        CW_BLCK2)- $this->ecCw;
        if($this->ecBlck2 > 0){ 
            for($i = 1; $i <=  $this->ecBlck2; $i++ ){
                $dataBlck2[$i] = substr ($this->strEncoded, $index,CW_BITS * $data2Len );
                unset($mssgPoly[$i]);
                $mssgPoly[$i] = new cIPoly();
                unset($ecPoly2[$i]);
                $ecPoly2[$i] = new cAPoly();
                //generate ecc polynomial
                for($k = 0; $k < count( $ecPolynomial[$this->ecCw ]); $k++){
                    $ecPoly2[$i]->addTerm(new cATerm($ecPolynomial[$this->ecCw ][$k],
                    $this->initExp2 - $k));
                }
                $exp = $this->initExp2;
                //generate message polynomial
                for($j = 0; $j < CW_BITS * $data2Len; $j += CW_BITS){
                    $coeff = bindec(substr($dataBlck2[$i], $j, CW_BITS));
                    $mssgPoly[$i]->addTerm(new cITerm($coeff, $exp));
                    $exp--;
                }
                if($debug){
                    print "Generator polynomial (Block 2) ".$ecPoly2[$i]."<br>";
                    printf("Data Block 2, %d : <br>%s<br>Message polynomial= <br>%s<br>",
                    $i, wordwrap($dataBlck2[$i], 64, "<br>", true), $mssgPoly[$i]);    
                }
                $ecBlck2[$i] = $this->computeEC($ecPoly2[$i], $mssgPoly[$i]);
                if($debug){
                    printf("Error Correction Block 2, %d : <br>%s<br>", $i,
                    wordwrap($ecBlck2[$i], 64, "<br>", true));
                }
                $index += CW_BITS * $data2Len;
           }
        }
        $finalSeq = "";
        if($data1Len >= $data2Len){
            $dataLen = $data1Len;
        }else{
            $dataLen = $data2Len;
        }
        //generate final sequence
        for($i = 0; $i < $dataLen; $i++){
            for($j = 1; $j <=  $this->ecBlck1; $j++ ){
                if($i * CW_BITS + CW_BITS <= strlen($dataBlck1[$j]) ){
                    $finalSeq .= substr( $dataBlck1[$j], $i * CW_BITS, CW_BITS);
                }
            }
            for($j = 1; $j <=  $this->ecBlck2; $j++ ){
                if($i * CW_BITS + CW_BITS <= strlen($dataBlck2[$j]) ){
                    $finalSeq .= substr( $dataBlck2[$j], $i * CW_BITS, CW_BITS);
                }
            }
        }
        for($i = 0; $i < $this->ecCw; $i++){
            for($j = 1; $j <=  $this->ecBlck1; $j++ ){
                $finalSeq .= substr( $ecBlck1[$j], $i * CW_BITS, CW_BITS);
            }
            for($j = 1; $j <=  $this->ecBlck2; $j++ ){
                $finalSeq .= substr( $ecBlck2[$j], $i * CW_BITS, CW_BITS);
            }
        }            
        //add reminder bits
        if( $this->remBits > 0){
            $finalSeq .= sprintf("%0". $this->remBits."b", 0);
        }
        $this->finalSeq = $finalSeq;
        if($debug){
            printf("Final sequence: <br>%s<br>",
            wordwrap($this->finalSeq, 64, "<br>", true));
        }
    }    
    //calculate EC of a given block
    private function computeEC($ecPoly, $mssgPoly){
        if(DEBUG && (DEBUG_LEVEL & EQR_EC)){
                $debug = true;
        }else{
            $debug = false;
        }
        $tmpAPoly1 = $ecPoly;
        $tmpAPoly2 = $mssgPoly->toAPoly();
        $tmpIPoly2 = $tmpAPoly2->toIPoly();
        
        do{
            $tmpAPoly3 = $tmpAPoly1->multiply($tmpAPoly2->get1stTerm());
            if($debug){
                print $tmpAPoly1."<br>";
                print $tmpAPoly2->get1stTerm(). " Multiply<br>";
                print $tmpAPoly3. "  = <br>";           
            }
            $tmpIPoly3 = $tmpAPoly3->toIPoly();
            $tmpIPoly1 = $tmpIPoly3->XorTerms($tmpIPoly2);
            
            if($debug){
                print $tmpIPoly3." integer <br>";
                print $tmpIPoly2." xor <br>";
                print $tmpIPoly1." =<br><br>";
            }
            $tmpAPoly1->decExp();
            $tmpTerm   = $tmpAPoly1->getLastterm();
            $tmpAPoly2 = $tmpIPoly1->toAPoly();
            
            while($tmpAPoly1->get1stTerm()->getExp() > $tmpAPoly2->get1stTerm()->getExp()){
                $tmpAPoly1->decExp();
            }
            $tmpIPoly2 = $tmpIPoly1;
        }while($tmpTerm->getExp() >= 0);
        for($i = $this->ecCw - 1; $i >= 0; $i--){
            $ecCoeff[$i]=0;
        }
        foreach($tmpIPoly1->terms as $key => $term){
            $ecCoeff[$term->getExp()] = $term->getCoeff();
            
        }
        $ecStr="";
        for( $i = $this->ecCw - 1; $i >= 0; $i--){
            $ecStr .= sprintf("%0".CW_BITS."b", $ecCoeff[$i]);
        }
        return $ecStr;
    }
    // fill matrix
    private function matrix(){
        if(DEBUG && (DEBUG_LEVEL & EQR_MATRIX)){
            $debug = true;
        }else{
            $debug = false;
        }
        $this->matrixLen = 17 + $this->version * 4;
        for($i = 0; $i < $this->matrixLen; $i++){
            for($j = 0; $j < $this->matrixLen; $j++){
                $this->dataMatrix[$i][$j] = 0;
            }
        }
        $this->setPDP();
        $this->setTP();
        $this->setPAP();
        $this->setData();
        if($debug){
            printf ("Matrix Length = %d<br>", $this->matrixLen);
            echo"<table>";  
            for($ren = 0; $ren < $this->matrixLen; $ren++){
                echo "<tr>";
                for($col = 0; $col < $this->matrixLen; $col++){
                    echo "<td>".$this->dataMatrix[$ren][$col]."</td>";               
                }   
                echo "</tr>";
            }
            echo"</table>";           
        }
    }
    //fill matrix with final sequence
    private function setData(){
        if(DEBUG && (DEBUG_LEVEL & EQR_MATRIX)){
            $debug = true;
        }else{
            $debug = false;
        }    
        $max = $this->matrixLen - 1;
        $row = $max;
        $col = $max;
        $bits = strlen($this->finalSeq);
        $dir  = UP;
        $move = COL;
        if($debug){
            echo "Filling matrix with final sequence<br>";
        }
        for($i=0;$i<$bits;$i++){
            $bit = substr($this->finalSeq,$i,1);
            if($this->dataMatrix[$row][$col]<PDP_W){
                $this->dataMatrix[$row][$col] = $bit;
                 if($debug){
                    echo $row,"-",$col,"->",$bit."<br>";
                 }
            }else{
                $i--;
            }
            if($move == COL){
                $col += LEFT;
                $move = ROW;
            }else{//ROW
                $col += RIGHT;
                $row += $dir;
                $move = COL;
            }
            if( $row < 0 ){
                $dir = DOWN;
                $row += $dir;
                $col += (LEFT*2);
                if($col==6){
                    $col += LEFT;
                }
                $move = COL;
            }
            if( $row > $max ){
                $dir = UP;
                $row += $dir;
                $col += (LEFT*2);
                if($col==6){
                    $col += LEFT;
                }                    
                $move = COL;
            }
        }
    }
    //set PAP to keep space
    private function setPAP(){
        if($this->version>1){
            $PAPTable[2]  = array(6, 18);
            $PAPTable[3]  = array(6, 22);
            $PAPTable[4]  = array(6, 26);
            $PAPTable[5]  = array(6, 30);
            $PAPTable[6]  = array(6, 34);
            $PAPTable[7]  = array(6, 22, 38);
            $PAPTable[8]  = array(6, 24, 42);
            $PAPTable[9]  = array(6, 26, 46);
            $PAPTable[10] = array(6, 28, 50);
            $PAPTable[11] = array(6, 30, 54);
            $PAPTable[12] = array(6, 32, 58);
            $PAPTable[13] = array(6, 34, 62);
            $PAPTable[14] = array(6, 26, 46, 66);
            $PAPTable[15] = array(6, 26, 48, 70);
            $PAPTable[16] = array(6, 26, 50, 74);
            $PAPTable[17] = array(6, 30, 54, 78);
            $PAPTable[18] = array(6, 30, 56, 82);
            $PAPTable[19] = array(6, 30, 58, 86);
            $PAPTable[20] = array(6, 34, 62, 90);
            $PAPTable[21] = array(6, 28, 50, 72, 94);
            $PAPTable[22] = array(6, 26, 50, 74, 98);
            $PAPTable[23] = array(6, 30, 54, 78, 102);
            $PAPTable[24] = array(6, 28, 54, 80, 106);
            $PAPTable[25] = array(6, 32, 58, 84, 110);
            $PAPTable[26] = array(6, 30, 58, 86, 114);
            $PAPTable[27] = array(6, 34, 62, 90, 118);
            $PAPTable[28] = array(6, 26, 50, 74, 98, 122);
            $PAPTable[29] = array(6, 30, 54, 78, 102, 126);
            $PAPTable[30] = array(6, 26, 52, 78, 104, 130);
            $PAPTable[31] = array(6, 30, 56, 82, 108, 134);
            $PAPTable[32] = array(6, 34, 60, 86, 112, 138);
            $PAPTable[33] = array(6, 30, 58, 86, 114, 142);
            $PAPTable[34] = array(6, 34, 62, 90, 118, 146);
            $PAPTable[35] = array(6, 30, 54, 78, 102, 126, 150);
            $PAPTable[36] = array(6, 24, 50, 76, 102, 128, 154);
            $PAPTable[37] = array(6, 28, 54, 80, 106, 132, 158);
            $PAPTable[38] = array(6, 32, 58, 84, 110, 136, 162);
            $PAPTable[39] = array(6, 26, 54, 82, 110, 138, 166);
            $PAPTable[40] = array(6, 30, 58, 86, 114, 142, 170);
            
            $aRow  = $PAPTable[$this->version];
            $aCol  = $PAPTable[$this->version];
            foreach($aRow as $row){
                foreach($aCol as $col){
                    if($this->dataMatrix[$row][$col] != PDP_W && $this->dataMatrix[$row][$col] != PDP_B){
                        for($i=-2; $i < 3; $i++){
                            switch($i){
                                case -2:
                                case 2:
                                    $this->dataMatrix[$row + $i][$col - 2] = PAP_B;
                                    $this->dataMatrix[$row + $i][$col - 1] = PAP_B;
                                    $this->dataMatrix[$row + $i][$col]     = PAP_B;
                                    $this->dataMatrix[$row + $i][$col + 1] = PAP_B;
                                    $this->dataMatrix[$row + $i][$col + 2] = PAP_B;
                                break;
                                case -1:
                                case 1:
                                    $this->dataMatrix[$row + $i][$col - 2] = PAP_B;
                                    $this->dataMatrix[$row + $i][$col - 1] = PAP_W;
                                    $this->dataMatrix[$row + $i][$col]     = PAP_W;
                                    $this->dataMatrix[$row + $i][$col + 1] = PAP_W;
                                    $this->dataMatrix[$row + $i][$col + 2] = PAP_B;
                                break;
                                case 0:
                                    $this->dataMatrix[$row + $i][$col - 2] = PAP_B;
                                    $this->dataMatrix[$row + $i][$col - 1] = PAP_W;
                                    $this->dataMatrix[$row + $i][$col]     = PAP_B;
                                    $this->dataMatrix[$row + $i][$col + 1] = PAP_W;
                                    $this->dataMatrix[$row + $i][$col + 2] = PAP_B;
                                break;                            
                            }
                        }
                    }
                }
            }
        }
        //information and version (white) just to keep space
        for($i = 0; $i < 6; $i++){
           $this->dataMatrix[$i][8]   = INFO_W;
           $this->dataMatrix[8][$i]   = INFO_W;
        }
        $i++;
        $this->dataMatrix[$i][8]      = INFO_W;
        $this->dataMatrix[8][$i]      = INFO_W;
        $this->dataMatrix[8][$i + 1]  = INFO_W;
        
        for($i = 0; $i < 7; $i++){
           $this->dataMatrix[$this->matrixLen - 7 + $i][8] = INFO_W;
           $this->dataMatrix[8][$this->matrixLen - 7 + $i] = INFO_W;
        }
        $this->dataMatrix[8][$this->matrixLen - 8] = INFO_W;
        
        if($this->version > 7){
            for($i = 0; $i < 6; $i++){
                $this->dataMatrix[$this->matrixLen - 11][$i] = INFO_W;
                $this->dataMatrix[$this->matrixLen - 10][$i] = INFO_W;
                $this->dataMatrix[$this->matrixLen - 9][$i]  = INFO_W;
                $this->dataMatrix[$i][$this->matrixLen - 11] = INFO_W;
                $this->dataMatrix[$i][$this->matrixLen - 10] = INFO_W;
                $this->dataMatrix[$i][$this->matrixLen - 9]  = INFO_W;
            }
        }
    }
    //set TP to keep space
    private function setTP(){
        $color = TP_B;
        for($i = 8; $i < $this->matrixLen - 8; $i++){
            $this->dataMatrix[$i][6] = $color;
            $this->dataMatrix[6][$i] = $color;
            if($color==TP_B){
                $color = TP_W;
            }else{
                $color = TP_B;
            }
        }
        $this->dataMatrix[$this->matrixLen - 8][8] = TP_B;
    }
    //set PDP to keep space
    private function setPDP(){
        $offset = $this->matrixLen - 8;        
        for( $i = 0; $i < 8; $i++){
            switch ($i){
                case 0:
                case 6:
                       $this->dataMatrix[$i][0] = PDP_B;
                       $this->dataMatrix[$i][1] = PDP_B;
                       $this->dataMatrix[$i][2] = PDP_B;
                       $this->dataMatrix[$i][3] = PDP_B;
                       $this->dataMatrix[$i][4] = PDP_B;
                       $this->dataMatrix[$i][5] = PDP_B;
                       $this->dataMatrix[$i][6] = PDP_B;
                       $this->dataMatrix[$i][7] = PDP_W;

                       $this->dataMatrix[$i][0 + $offset] = PDP_W;
                       $this->dataMatrix[$i][1 + $offset] = PDP_B;
                       $this->dataMatrix[$i][2 + $offset] = PDP_B;
                       $this->dataMatrix[$i][3 + $offset] = PDP_B;
                       $this->dataMatrix[$i][4 + $offset] = PDP_B;
                       $this->dataMatrix[$i][5 + $offset] = PDP_B;
                       $this->dataMatrix[$i][6 + $offset] = PDP_B;
                       $this->dataMatrix[$i][7 + $offset] = PDP_B;
                       break;
                case 1:
                case 5:
                       $this->dataMatrix[$i][0] = PDP_B;
                       $this->dataMatrix[$i][1] = PDP_W;
                       $this->dataMatrix[$i][2] = PDP_W;
                       $this->dataMatrix[$i][3] = PDP_W;
                       $this->dataMatrix[$i][4] = PDP_W;
                       $this->dataMatrix[$i][5] = PDP_W;
                       $this->dataMatrix[$i][6] = PDP_B;
                       $this->dataMatrix[$i][7] = PDP_W;

                       $this->dataMatrix[$i][0 + $offset] = PDP_W;
                       $this->dataMatrix[$i][1 + $offset] = PDP_B;
                       $this->dataMatrix[$i][2 + $offset] = PDP_W;
                       $this->dataMatrix[$i][3 + $offset] = PDP_W;
                       $this->dataMatrix[$i][4 + $offset] = PDP_W;
                       $this->dataMatrix[$i][5 + $offset] = PDP_W;
                       $this->dataMatrix[$i][6 + $offset] = PDP_W;
                       $this->dataMatrix[$i][7 + $offset] = PDP_B;
                   break;
                case 2:
                case 3:
                case 4:
                       $this->dataMatrix[$i][0] = PDP_B;
                       $this->dataMatrix[$i][1] = PDP_W;
                       $this->dataMatrix[$i][2] = PDP_B;
                       $this->dataMatrix[$i][3] = PDP_B;
                       $this->dataMatrix[$i][4] = PDP_B;
                       $this->dataMatrix[$i][5] = PDP_W;
                       $this->dataMatrix[$i][6] = PDP_B;
                       $this->dataMatrix[$i][7] = PDP_W;

                       $this->dataMatrix[$i][0 + $offset] = PDP_W;
                       $this->dataMatrix[$i][1 + $offset] = PDP_B;
                       $this->dataMatrix[$i][2 + $offset] = PDP_W;
                       $this->dataMatrix[$i][3 + $offset] = PDP_B;
                       $this->dataMatrix[$i][4 + $offset] = PDP_B;
                       $this->dataMatrix[$i][5 + $offset] = PDP_B;
                       $this->dataMatrix[$i][6 + $offset] = PDP_W;
                       $this->dataMatrix[$i][7 + $offset] = PDP_B;
                       break;
                case 7:
                       $this->dataMatrix[$i][0] = PDP_W;
                       $this->dataMatrix[$i][1] = PDP_W;
                       $this->dataMatrix[$i][2] = PDP_W;
                       $this->dataMatrix[$i][3] = PDP_W;
                       $this->dataMatrix[$i][4] = PDP_W;
                       $this->dataMatrix[$i][5] = PDP_W;
                       $this->dataMatrix[$i][6] = PDP_W;
                       $this->dataMatrix[$i][7] = PDP_W;

                       $this->dataMatrix[$i][0 + $offset] = PDP_W;
                       $this->dataMatrix[$i][1 + $offset] = PDP_W;
                       $this->dataMatrix[$i][2 + $offset] = PDP_W;
                       $this->dataMatrix[$i][3 + $offset] = PDP_W;
                       $this->dataMatrix[$i][4 + $offset] = PDP_W;
                       $this->dataMatrix[$i][5 + $offset] = PDP_W;
                       $this->dataMatrix[$i][6 + $offset] = PDP_W;
                       $this->dataMatrix[$i][7 + $offset] = PDP_W;
                       break;
            }
            switch ($i){
                case 0:
                       $this->dataMatrix[$i + $offset][0] = PDP_W;
                       $this->dataMatrix[$i + $offset][1] = PDP_W;
                       $this->dataMatrix[$i + $offset][2] = PDP_W;
                       $this->dataMatrix[$i + $offset][3] = PDP_W;
                       $this->dataMatrix[$i + $offset][4] = PDP_W;
                       $this->dataMatrix[$i + $offset][5] = PDP_W;
                       $this->dataMatrix[$i + $offset][6] = PDP_W;
                       $this->dataMatrix[$i + $offset][7] = PDP_W;
                       break;
                case 1:
                case 7:
                       $this->dataMatrix[$i + $offset][0] = PDP_B;
                       $this->dataMatrix[$i + $offset][1] = PDP_B;
                       $this->dataMatrix[$i + $offset][2] = PDP_B;
                       $this->dataMatrix[$i + $offset][3] = PDP_B;
                       $this->dataMatrix[$i + $offset][4] = PDP_B;
                       $this->dataMatrix[$i + $offset][5] = PDP_B;
                       $this->dataMatrix[$i + $offset][6] = PDP_B;
                       $this->dataMatrix[$i + $offset][7] = PDP_W;                   
                   break;
                case 3:
                case 4:
                case 5:
                       $this->dataMatrix[$i + $offset][0] = PDP_B;
                       $this->dataMatrix[$i + $offset][1] = PDP_W;
                       $this->dataMatrix[$i + $offset][2] = PDP_B;
                       $this->dataMatrix[$i + $offset][3] = PDP_B;
                       $this->dataMatrix[$i + $offset][4] = PDP_B;
                       $this->dataMatrix[$i + $offset][5] = PDP_W;
                       $this->dataMatrix[$i + $offset][6] = PDP_B;
                       $this->dataMatrix[$i + $offset][7] = PDP_W;
                       break;
                case 2:
                case 6:
                       $this->dataMatrix[$i + $offset][0] = PDP_B;
                       $this->dataMatrix[$i + $offset][1] = PDP_W;
                       $this->dataMatrix[$i + $offset][2] = PDP_W;
                       $this->dataMatrix[$i + $offset][3] = PDP_W;
                       $this->dataMatrix[$i + $offset][4] = PDP_W;
                       $this->dataMatrix[$i + $offset][5] = PDP_W;
                       $this->dataMatrix[$i + $offset][6] = PDP_B;
                       $this->dataMatrix[$i + $offset][7] = PDP_W;
                       break;
            }
        }
    }
    //generate, evaluate, select best mask
    private function masking(){
        if(DEBUG && (DEBUG_LEVEL & EQR_MASKING)){
            $debug = true;
        }else{
            $debug = false;
        }
        $mDataMatrix = array($this->dataMatrix, $this->dataMatrix, 
                             $this->dataMatrix, $this->dataMatrix,
                             $this->dataMatrix, $this->dataMatrix,
                             $this->dataMatrix, $this->dataMatrix);
        for($i = 0 ; $i < 8; $i++){
            for($row = 0; $row < $this->matrixLen; $row++){
                for($col = 0; $col < $this->matrixLen; $col++){
                    if($this->dataMatrix[$row][$col] < PDP_W){
                        //select mask
                        switch($i){
                            case 0: $replace = (($row + $col) % 2 == 0);
                                    break; 
                            case 1: $replace = ($row % 2 == 0);
                                    break; 
                            case 2: $replace = ($col % 3 == 0 );
                                    break; 
                            case 3: $replace = (($row + $col) % 3 == 0 );
                                    break; 
                            case 4: $replace = (((int)($row / 2) + 
                                    (int)($col / 3))% 2 == 0 );
                                    break; 
                            case 5: $replace = (($row * $col) % 2 + 
                                    ($row * $col) % 3 == 0 );
                                    break; 
                            case 6: $replace = ((($row * $col) % 2 + 
                                    ($row * $col) %3) % 2 == 0 );
                                    break; 
                            case 7: $replace = ((($row * $col) % 3 + 
                                    ($row + $col) % 2) % 2 == 0 );
                                    break; 
                        }
                        if( $replace ){
                            if($mDataMatrix[$i][$row][$col] == 0){
                                $mDataMatrix[$i][$row][$col] = 1;
                            }else{
                                $mDataMatrix[$i][$row][$col] = 0;
                            }
                        }                   
                    }else{
                        //clean reserved space
                        switch($this->dataMatrix[$row][$col]){
                            case PDP_B:
                            case PAP_B:
                            case INFO_B:
                            case TP_B:
                                $mDataMatrix[$i][$row][$col] = 1;
                                break;
                            default :
                                $mDataMatrix[$i][$row][$col] = 0;
                                break;
                        }
                    }
                }
            }
            $this->format($mDataMatrix[$i], $i);
        }
        
        //evaluation
        $n1    = 3;
        $n2    = 3;
        $n3    = 40;
        $n4    = 10;
        $score = array(0, 0, 0, 0, 0, 0, 0, 0);
        $rule1 = array(0, 0, 0, 0, 0, 0, 0, 0);
        $rule2 = array(0, 0, 0, 0, 0, 0, 0, 0);
        $rule3 = array(0, 0, 0, 0, 0, 0, 0, 0);
        $rule4 = array(0, 0, 0, 0, 0, 0, 0, 0);
        $i     = array(0, 0, 0, 0, 0, 0, 0, 0);
        $p     = array(0, 0, 0, 0, 0, 0, 0, 0);
        $k     = array(0, 0, 0, 0, 0, 0, 0, 0);
        $blk   = array(0, 0, 0, 0, 0, 0, 0, 0);
        $bit   = -1;
        $dim   = $this->matrixLen * $this->matrixLen;
        $tmpStr = array("", "", "", "", "", "", "", "");
        for ($j = 0; $j < 8; $j++){
            for($r = 0; $r < $this->matrixLen; $r++){
                for($c = 0; $c < $this->matrixLen; $c++){
                    if($mDataMatrix[$j][$r][$c] == 1){ 
                        $k[$j]++;
                    }
                    if( isset($mDataMatrix[$j][$r + 1][$c + 1]) ){
                        if($mDataMatrix[$j][$r][$c] == $mDataMatrix[$j][$r][$c + 1] &&
                           $mDataMatrix[$j][$r][$c] == $mDataMatrix[$j][$r + 1][$c] &&
                           $mDataMatrix[$j][$r][$c] == $mDataMatrix[$j][$r + 1][$c + 1]){
                                $blk[$j]++;
                           }
                    }
                    $tmpStr[$j] .= $mDataMatrix[$j][$r][$c];
                }
                
                preg_match_all("/111111*/", $tmpStr[$j], $matches);
                $n = count($matches[0]);
                for($m = 0; $m < $n; $m++){
                    $i[$j] += 3 + strlen($matches[0][$m]) - 5;
                }
                preg_match_all("/000000*/", $tmpStr[$j], $matches);
                $n = count($matches[0]);
                for($m = 0; $m < $n; $m++){
                    $i[$j] += 3 + strlen($matches[0][$m]) - 5;
                }
                $p[$j] += substr_count($tmpStr[$j], "10111010000") + 
                          substr_count($tmpStr[$j], "00001011101");
                $tmpStr[$j] = "";
            }
            $k[$j] = (int)(abs((int)($k[$j] / $dim * 100 - 50)) / 5);
            $rule4[$j] += $k[$j] * $n4;
        }
        
        $tmpStr = array("", "", "", "", "", "", "", "");
        for ($j = 0; $j < 8; $j++){
            for($c = 0; $c < $this->matrixLen; $c++){
                for($r = 0; $r < $this->matrixLen; $r++){
                    $tmpStr[$j] .= $mDataMatrix[$j][$r][$c];
                }
                preg_match_all("/111111*/", $tmpStr[$j], $matches);
                $n=count($matches[0]);
                for($m = 0; $m < $n; $m++){
                    $i[$j] += $n1 + strlen($matches[0][$m]) - 5;
                }
                preg_match_all("/000000*/", $tmpStr[$j], $matches);
                $n = count($matches[0]);
                for($m = 0; $m < $n; $m++){
                    $i[$j] += $n1 + strlen($matches[0][$m]) - 5;
                }
                $p[$j] += substr_count($tmpStr[$j], "10111010000") + 
                          substr_count($tmpStr[$j], "00001011101");
                $tmpStr[$j] = "";
            }
            $rule1[$j] = $i[$j];
            $rule2[$j] = $n2 * $blk[$j];
            $rule3[$j] = $p[$j] * $n3;
            $score[$j] = $rule1[$j] + $rule2[$j] +$rule3[$j] + $rule4[$j];
        }
        
        $minScore = $score[0]; 
        for($i = 1; $i < 8; $i++){
            if($score[$i] < $minScore){
                $minScore = $score[$i];
                $this->mask = $i;
            }
        }
        if($debug){
            for( $i = 0;$i < 8; $i++){
                printf ("Score Mask %d = %d<br>Rule 1 = %d<br>Rule 2 = %d<br>".
                "Rule 3 = %d<br>Rule 4 = %d<br>", $i, $score[$i], $rule1[$i], 
                $rule2[$i], $rule3[$i], $rule4[$i]);
                echo"<table>";   
                for($ren = 0; $ren < $this->matrixLen; $ren++){
                    echo "<tr>";
                    for($col = 0; $col < $this->matrixLen; $col++){
                        echo "<td>".$mDataMatrix[$i][$ren][$col]."</td>";               
                    }   
                    echo "</tr>";
                }
                echo"</table>";          
            }
            printf ("Selected Mask = %d<br>", $this->mask);
        }
        $this->dataMatrix = $mDataMatrix[$this->mask];
    }
    private function setInfo(&$data,$mask){
        if(DEBUG && (DEBUG_LEVEL & EQR_FORMAT)){
                $debug = true;
        }else{
            $debug = false;
        }

        $infoTable[ECL_L][0] = "111011111000100";
        $infoTable[ECL_L][1] = "111001011110011";
        $infoTable[ECL_L][2] = "111110110101010";
        $infoTable[ECL_L][3] = "111100010011101";
        $infoTable[ECL_L][4] = "110011000101111";
        $infoTable[ECL_L][5] = "110001100011000";
        $infoTable[ECL_L][6] = "110110001000001";
        $infoTable[ECL_L][7] = "110100101110110";
        $infoTable[ECL_M][0] = "101010000010010";
        $infoTable[ECL_M][1] = "101000100100101";
        $infoTable[ECL_M][2] = "101111001111100";
        $infoTable[ECL_M][3] = "101101101001011";
        $infoTable[ECL_M][4] = "100010111111001";
        $infoTable[ECL_M][5] = "100000011001110";
        $infoTable[ECL_M][6] = "100111110010111";
        $infoTable[ECL_M][7] = "100101010100000";
        $infoTable[ECL_Q][0] = "011010101011111";
        $infoTable[ECL_Q][1] = "011000001101000";
        $infoTable[ECL_Q][2] = "011111100110001";
        $infoTable[ECL_Q][3] = "011101000000110";
        $infoTable[ECL_Q][4] = "010010010110100";
        $infoTable[ECL_Q][5] = "010000110000011";
        $infoTable[ECL_Q][6] = "010111011011010";
        $infoTable[ECL_Q][7] = "010101111101101";
        $infoTable[ECL_H][0] = "001011010001001";
        $infoTable[ECL_H][1] = "001001110111110";
        $infoTable[ECL_H][2] = "001110011100111";
        $infoTable[ECL_H][3] = "001100111010000";
        $infoTable[ECL_H][4] = "000011101100010";
        $infoTable[ECL_H][5] = "000001001010101";
        $infoTable[ECL_H][6] = "000110100001100";
        $infoTable[ECL_H][7] = "000100000111011";      
        $this->info = $infoTable[$this->errorLevel][$mask];
       
        $index = 0;
        //info in row
        if($debug){
            echo "Filling format info<br>";
        }
        for($i = 0; $i < 6; $i++){
           $data[8][$i]     = substr($this->info, $index, 1);
           if($debug){
                printf("8,%d -> %d<br>", $i, substr($this->info, $index, 1));
           }
           $index++;
        }
        $i++;
        $data[8][$i]     = substr($this->info, $index, 1);
        if($debug){
           printf("8,%d -> %d<br>", $i, substr($this->info, $index, 1));
        }        
        $index++;
        for($i = 0; $i < 8; $i++){
           $data[8][$this->matrixLen - 8 + $i] = substr($this->info, $index, 1);
           if($debug){
                printf("8,%d -> %d<br>", $this->matrixLen - 8 + $i, 
                substr($this->info, $index, 1));
           }
           $index++;
        }
        //info in col
        $index = 0;
        for($i = 1; $i < 8; $i++){
           $data[$this->matrixLen -  $i][8] = substr($this->info, $index, 1);
           if($debug){
                printf("%d, 8 -> %d<br>", $this->matrixLen -  $i,
                substr($this->info, $index, 1));
           }
           $index++;
        }
        for($i = 0 ; $i < 2 ; $i++){
            $data[ 8 - $i][8] = substr($this->info, $index, 1);
           if($debug){
                printf("%d, 8 -> %d<br>", 8 - $i, substr($this->info, $index, 1));
           }
            $index++;
        }
        for($i = 0 ; $i< 6 ; $i++){
            $data[ 5 - $i][8] = substr($this->info, $index, 1);
           if($debug){
                printf("%d, 8 -> %d<br>",5 - $i, substr($this->info, $index, 1));
           }
            $index++;
        }
        if($debug){
            printf("Format Information %s <br>", $this->info);
        }
    }
    private function setVerInfo(&$data){
        if(DEBUG && (DEBUG_LEVEL & EQR_FORMAT)){
                $debug = true;
        }else{
            $debug = false;
        }
        $verInfoTable[7]  = "001010010011111000";
        $verInfoTable[8]  = "000111101101000100";
        $verInfoTable[9]  = "100110010101100100";
        $verInfoTable[10] = "011001011001010100";
        $verInfoTable[11] = "011011111101110100";
        $verInfoTable[12] = "001000110111001100";
        $verInfoTable[13] = "111000100001101100";
        $verInfoTable[14] = "010110000011011100";
        $verInfoTable[15] = "000101001001111100";
        $verInfoTable[16] = "000111101101000010";
        $verInfoTable[17] = "010111010001100010";
        $verInfoTable[18] = "111010000101010010";
        $verInfoTable[19] = "001001100101110010";
        $verInfoTable[20] = "011001011001001010";
        $verInfoTable[21] = "011000001011101010";
        $verInfoTable[22] = "100100110001011010";
        $verInfoTable[23] = "000110111111111010";
        $verInfoTable[24] = "001000110111000110";
        $verInfoTable[25] = "000100001111100110";
        $verInfoTable[26] = "110101011111010110";
        $verInfoTable[27] = "000001110001110110";
        $verInfoTable[28] = "010110000011001110";
        $verInfoTable[29] = "001111110011101110";
        $verInfoTable[30] = "101011101011011110";
        $verInfoTable[31] = "000000101001111110";
        $verInfoTable[32] = "101010111001000001";
        $verInfoTable[33] = "000001111011100001";
        $verInfoTable[34] = "010111010001010001";
        $verInfoTable[35] = "011111001111110001";
        $verInfoTable[36] = "110100001101001001";
        $verInfoTable[37] = "001110100001101001";
        $verInfoTable[38] = "001001100101011001";
        $verInfoTable[39] = "010000010101111001";
        $verInfoTable[40] = "100101100011000101";
        $this->verInfo    = $verInfoTable[$this->version];        
        $index = 0;
        if($debug){
            echo "Filling version info<br>";
        }
        for($i = 0; $i < 6; $i++){
            for($j = 0; $j < 3; $j++){
                $data[$i][$this->matrixLen - 11 + $j] = 
                substr($this->verInfo,$index , 1);           
                $data[$this->matrixLen - 11 + $j][$i] = 
                substr($this->verInfo,$index , 1);
                $index++;
                if($debug){
                    echo $i, ",", $this->matrixLen - 11 + $j, "->", 
                    substr($this->verInfo, $index, 1), "<br>";
                    echo $this->matrixLen - 11 + $j, ",", $i, "->",
                    substr($this->verInfo, $index, 1), "<br>";
                }
            }
        }
        if($debug){
            printf("Version Information %s <br>", $this->verInfo);
        }
    }
    //Set format and version information
    private function format(&$data, $mask){
        $this->setInfo($data, $mask);
        if($this->version >= 7){
            $this->setVerInfo($data);
        }
    }
}
/**
 * Auxiliar classes 
 * cATerm  Alpha Term
 * @version   1.0
 * @author    Omar Eduardo Ortiz Garza <oortiz@tbanc.com>
 * @copyright (c) 2012-2013 Omar Eduardo Ortiz Garza
 * @since     Friday, November 30, 2012
 **/
class cATerm{
    private $aExp   = 0;
    private $xExp   = 0;
    public function __construct($aExp, $xExp){
        if(is_int($aExp)){
            $this->aExp = $aExp;
        }
        if(is_int($xExp)){
            $this->xExp = $xExp;
        }
    }
    //transfor Alpha term in Integer Term
    public function toInt (){       
        $iTerm = new cITerm(cLogTable::aToInt($this->aExp), $this->xExp);
        return $iTerm;
    }
    //Multiply Alpha terms, 
    //if Exponent greater than 255 then exponen = exponent mod 256
    public function multiply($term){
        if( $term instanceof cATerm){
            $expA = $term->aExp + $this->aExp;
            if($expA > 255){
                $expA = $expA % 255;
            }
            $result = new cATerm( $expA, $this->xExp);
            return $result;
        }        
        return false;
    }
    public function __toString(){
        return (string) "a^".$this->aExp."X^".$this->xExp;
    }
    public function decExp(){
        $this->xExp--;
    }
    public function getExp(){
        return $this->xExp;
    }
}
/**
 * Auxiliar classes 
 * cITerm  Integer Term
 * @version   1.0
 * @author    Omar Eduardo Ortiz Garza <oortiz@tbanc.com>
 * @copyright (c) 2012-2013 Omar Eduardo Ortiz Garza
 * @since     Friday, November 30, 2012
 **/
class cITerm{
    private $xCoeff = 0;
    private $xExp   = 0;
    public function __construct($xCoeff,$xExp){
        if(is_int($xCoeff)){
            $this->xCoeff = $xCoeff;
        }
        if(is_int($xExp)){
            $this->xExp = $xExp;
        }
    }
    // Covert to Alpha notation
    function toAlp (){       
        $aTerm = new cATerm(cLogTable::iToA($this->xCoeff), $this->xExp);
        return $aTerm;
    }
    function __toString(){
        return (string) $this->xCoeff."X^".$this->xExp;
    }
    function getCoeff(){
        return $this->xCoeff;
    }
    function getExp(){
        return $this->xExp;
    }
}
/**
 * Auxiliar classes 
 * cAPoly  Polynomial in Alpha notation
 * @version   1.0
 * @author    Omar Eduardo Ortiz Garza <oortiz@tbanc.com>
 * @copyright (c) 2012-2013 Omar Eduardo Ortiz Garza
 * @since     Friday, November 30, 2012
 **/
class cAPoly {
    public  $terms = array();
    private $count = 0;
    
    public function __construct(){
        $this->terms = array();
        $this->count = 0;
    }
    public function get1stTerm(){
        return $this->terms[0];
    }
    public function getLastTerm(){
        return end($this->terms);
    } 
    public function addTerm( $term){
        if( $term instanceof cATerm){
            $this->terms[] = $term;
            $this->count++;
        }
    }
    // Convert to Integer notation
    public function toIPoly(){
        $terms = array();
        reset($this->terms);
        $iPoly = new cIPoly();
        foreach($this->terms as $key => $value){
            $iPoly->addTerm($value->toInt());
        }
        return $iPoly;
    }
    // Multiply polynomial by alpha term
    public function multiply($term){
        if($term instanceof cATerm){
            $result = new cAPoly();
            foreach($this->terms as $key => $value){
                $result->addTerm($this->terms[$key]->multiply($term));
            }  
            $result->sortTerms();
            return $result;
        }
        return false;
    }
    private function sortTerms(){
        $tempPoly = array();
        $maxExp = 0;
        foreach($this->terms as $term){
            if($term->getExp() > $maxExp){
                $maxExp = $term->getExp();
            }
        }
        while ( $maxExp >= 0){
            foreach($this->terms as $term){
                if($term->getExp() == $maxExp){
                    $tempPoly[] = $term;
                }
            }
            $maxExp--;
        }
        $this->terms = $tempPoly;
    }
    public function __toString(){
        $string = "";
        reset($this->terms);
        foreach($this->terms as $key => $term){
            $string .= $term->__toString()." + ";
        }
        $string=substr($string, 0, -1);
        return (string) $string;
    }  
    public function decExp(){
        reset($this->terms);
        foreach( $this->terms as $key => $term){
            $term->decExp();
        }
    }

}
/**
 * Auxiliar classes 
 * cIPoly  Polynomial in integer notation
 * @version   1.0
 * @author    Omar Eduardo Ortiz Garza <oortiz@tbanc.com>
 * @copyright (c) 2012-2013 Omar Eduardo Ortiz Garza
 * @since     Friday, November 30, 2012
 **/
class cIPoly {
    public  $terms = array();
    private $count = 0;
    function __construct(){
        
    }
    public function get1stTerm(){
        return $this->terms[0];
    }
    public function getLastTerm(){
        return end($this->terms);
    } 
    public function addTerm( $term){
        if( $term instanceof cITerm){
            $this->terms[] = $term;
            $this->count++;
        }
    }
    // Convert to Alpha notation
    public function toAPoly(){
        $terms = array();
        $aPoly = new cAPoly();
        foreach($this->terms as $key => $value){
            $aPoly->addTerm($value->toAlp());
        }
        return $aPoly;
    }
    public function __toString(){
        $string = "";
        foreach($this->terms as $key => $term){
            $string .= $term->__toString()." + ";
        }
        $string=substr($string, 0, -1);
        return (string) $string;
    } 
    // return polynomial after Xor coefficients
    public function XorTerms($poly){
        $tmp  = array();
        foreach($this->terms as $key => $term){
            $tmp[$term->getExp()] = $term->getCoeff();
        }
        foreach($poly->terms as $key => $term){
            if(isset($tmp[$term->getExp()])){
                $tmp[$term->getExp()] = $tmp[$term->getExp()] ^ $term->getCoeff();
            }else{
                $tmp[$term->getExp()] = $term->getCoeff();
            }
        }
        $iPoly = new cIPoly();
        $delTerm = true;
        foreach ($tmp as $exp => $coeff){
            if(!$delTerm || $coeff != 0){
                $iPoly->addTerm(new cITerm($coeff,$exp));
                $delTerm = false;
            }
        }
        $iPoly->sortTerms();
        return $iPoly;
    }
    function sortTerms(){
        $tempPoly = array();
        $maxExp = 0;
        foreach($this->terms as $term){
            if($term->getExp() > $maxExp){
                $maxExp = $term->getExp();
            }
        }
        while ( $maxExp >= 0){
            foreach($this->terms as $term){
                if($term->getExp() == $maxExp){
                    $tempPoly[] = $term;
                }
            }
            $maxExp--;
        }
        $this->terms = $tempPoly;
    }    
}
/**
 * Auxiliar classes 
 * cDebugTime this class take the time between two events and the total time
 * @version   1.0
 * @author    Omar Eduardo Ortiz Garza <oortiz@tbanc.com>
 * @copyright (c) 2012-2013 Omar Eduardo Ortiz Garza
 * @since     Friday, November 30, 2012
 **/
class cDebugTime{
    private $marks   = array();
    private $counter = 0;
    function __construct(){
        $this->setMark("Start");
    }
    
    public function setMark($dscp){
        list($usec, $sec) = explode(" ", microtime());
        $time = (float)$usec + (float)$sec;
        $this->mark[DSCP][] = $dscp;
        $this->mark[TIME][] = $time;
        $this->counter ++;
    }
    public function __toString(){
        $table = "<table>";
        for($i=1; $i<$this->counter; $i++){
            $table .= sprintf("<tr><td>%s</td><td>%f</td><tr>",
            $this->mark[DSCP][$i], $this->mark[TIME][$i] - $this->mark[TIME][$i - 1]);
        }
        $table .= sprintf("<tr><td>Total Time (seconds)</td><td>%f</td><tr>",
        $this->mark[TIME][$this->counter - 1] - $this->mark[TIME][0]);
        $table .= "</table>";
        return $table;
    }
}
/**
 * Auxiliar classes 
 * cLogTable this class is used to covert Alpha To Integer and vice versa
 * @version   1.0
 * @author    Omar Eduardo Ortiz Garza <oortiz@tbanc.com>
 * @copyright (c) 2012-2013 Omar Eduardo Ortiz Garza
 * @since     Friday, November 30, 2012
 **/
class cLogTable{   
    public static function aToInt($a){
        $expA[0]   = 1;   $expA[1]   = 2;   $expA[2]   = 4;   $expA[3]   = 8;
        $expA[4]   = 16;  $expA[5]   = 32;  $expA[6]   = 64;  $expA[7]   = 128; 
        $expA[8]   = 29;  $expA[9]   = 58;  $expA[10]  = 116; $expA[11]  = 232; 
        $expA[12]  = 205; $expA[13]  = 135; $expA[14]  = 19;  $expA[15]  = 38; 
        $expA[16]  = 76;  $expA[17]  = 152; $expA[18]  = 45;  $expA[19]  = 90; 
        $expA[20]  = 180; $expA[21]  = 117; $expA[22]  = 234; $expA[23]  = 201; 
        $expA[24]  = 143; $expA[25]  = 3;   $expA[26]  = 6;   $expA[27]  = 12; 
        $expA[28]  = 24;  $expA[29]  = 48;  $expA[30]  = 96;  $expA[31]  = 192; 
        $expA[32]  = 157; $expA[33]  = 39;  $expA[34]  = 78;  $expA[35]  = 156; 
        $expA[36]  = 37;  $expA[37]  = 74;  $expA[38]  = 148; $expA[39]  = 53; 
        $expA[40]  = 106; $expA[41]  = 212; $expA[42]  = 181; $expA[43]  = 119; 
        $expA[44]  = 238; $expA[45]  = 193; $expA[46]  = 159; $expA[47]  = 35; 
        $expA[48]  = 70;  $expA[49]  = 140; $expA[50]  = 5;   $expA[51]  = 10; 
        $expA[52]  = 20;  $expA[53]  = 40;  $expA[54]  = 80;  $expA[55]  = 160; 
        $expA[56]  = 93;  $expA[57]  = 186; $expA[58]  = 105; $expA[59]  = 210; 
        $expA[60]  = 185; $expA[61]  = 111; $expA[62]  = 222; $expA[63]  = 161; 
        $expA[64]  = 95;  $expA[65]  = 190; $expA[66]  = 97;  $expA[67]  = 194; 
        $expA[68]  = 153; $expA[69]  = 47;  $expA[70]  = 94;  $expA[71]  = 188; 
        $expA[72]  = 101; $expA[73]  = 202; $expA[74]  = 137; $expA[75]  = 15; 
        $expA[76]  = 30;  $expA[77]  = 60;  $expA[78]  = 120; $expA[79]  = 240; 
        $expA[80]  = 253; $expA[81]  = 231; $expA[82]  = 211; $expA[83]  = 187; 
        $expA[84]  = 107; $expA[85]  = 214; $expA[86]  = 177; $expA[87]  = 127; 
        $expA[88]  = 254; $expA[89]  = 225; $expA[90]  = 223; $expA[91]  = 163; 
        $expA[92]  = 91;  $expA[93]  = 182; $expA[94]  = 113; $expA[95]  = 226; 
        $expA[96]  = 217; $expA[97]  = 175; $expA[98]  = 67;  $expA[99]  = 134; 
        $expA[100] = 17;  $expA[101] = 34;  $expA[102] = 68;  $expA[103] = 136; 
        $expA[104] = 13;  $expA[105] = 26;  $expA[106] = 52;  $expA[107] = 104; 
        $expA[108] = 208; $expA[109] = 189; $expA[110] = 103; $expA[111] = 206; 
        $expA[112] = 129; $expA[113] = 31;  $expA[114] = 62;  $expA[115] = 124; 
        $expA[116] = 248; $expA[117] = 237; $expA[118] = 199; $expA[119] = 147; 
        $expA[120] = 59;  $expA[121] = 118; $expA[122] = 236; $expA[123] = 197; 
        $expA[124] = 151; $expA[125] = 51;  $expA[126] = 102; $expA[127] = 204; 
        $expA[128] = 133; $expA[129] = 23;  $expA[130] = 46;  $expA[131] = 92; 
        $expA[132] = 184; $expA[133] = 109; $expA[134] = 218; $expA[135] = 169; 
        $expA[136] = 79;  $expA[137] = 158; $expA[138] = 33;  $expA[139] = 66; 
        $expA[140] = 132; $expA[141] = 21;  $expA[142] = 42;  $expA[143] = 84; 
        $expA[144] = 168; $expA[145] = 77;  $expA[146] = 154; $expA[147] = 41; 
        $expA[148] = 82;  $expA[149] = 164; $expA[150] = 85;  $expA[151] = 170; 
        $expA[152] = 73;  $expA[153] = 146; $expA[154] = 57;  $expA[155] = 114; 
        $expA[156] = 228; $expA[157] = 213; $expA[158] = 183; $expA[159] = 115; 
        $expA[160] = 230; $expA[161] = 209; $expA[162] = 191; $expA[163] = 99; 
        $expA[164] = 198; $expA[165] = 145; $expA[166] = 63;  $expA[167] = 126; 
        $expA[168] = 252; $expA[169] = 229; $expA[170] = 215; $expA[171] = 179; 
        $expA[172] = 123; $expA[173] = 246; $expA[174] = 241; $expA[175] = 255; 
        $expA[176] = 227; $expA[177] = 219; $expA[178] = 171; $expA[179] = 75; 
        $expA[180] = 150; $expA[181] = 49;  $expA[182] = 98;  $expA[183] = 196; 
        $expA[184] = 149; $expA[185] = 55;  $expA[186] = 110; $expA[187] = 220; 
        $expA[188] = 165; $expA[189] = 87;  $expA[190] = 174; $expA[191] = 65; 
        $expA[192] = 130; $expA[193] = 25;  $expA[194] = 50;  $expA[195] = 100; 
        $expA[196] = 200; $expA[197] = 141; $expA[198] = 7;   $expA[199] = 14; 
        $expA[200] = 28;  $expA[201] = 56;  $expA[202] = 112; $expA[203] = 224; 
        $expA[204] = 221; $expA[205] = 167; $expA[206] = 83;  $expA[207] = 166; 
        $expA[208] = 81;  $expA[209] = 162; $expA[210] = 89;  $expA[211] = 178; 
        $expA[212] = 121; $expA[213] = 242; $expA[214] = 249; $expA[215] = 239; 
        $expA[216] = 195; $expA[217] = 155; $expA[218] = 43;  $expA[219] = 86; 
        $expA[220] = 172; $expA[221] = 69;  $expA[222] = 138; $expA[223] = 9; 
        $expA[224] = 18;  $expA[225] = 36;  $expA[226] = 72;  $expA[227] = 144; 
        $expA[228] = 61;  $expA[229] = 122; $expA[230] = 244; $expA[231] = 245; 
        $expA[232] = 247; $expA[233] = 243; $expA[234] = 251; $expA[235] = 235; 
        $expA[236] = 203; $expA[237] = 139; $expA[238] = 11;  $expA[239] = 22; 
        $expA[240] = 44;  $expA[241] = 88;  $expA[242] = 176; $expA[243] = 125; 
        $expA[244] = 250; $expA[245] = 233; $expA[246] = 207; $expA[247] = 131; 
        $expA[248] = 27;  $expA[249] = 54;  $expA[250] = 108; $expA[251] = 216; 
        $expA[252] = 173; $expA[253] = 71;  $expA[254] = 142; $expA[255] = 1; 
        return $expA[$a];
    }
    
    public static function iToA($int){
        $intToA[1]   = 0;   $intToA[2]   = 1;   $intToA[3]   = 25;  $intToA[4]   = 2;
        $intToA[5]   = 50;  $intToA[6]   = 26;  $intToA[7]   = 198; $intToA[8]   = 3;
        $intToA[9]   = 223; $intToA[10]  = 51;  $intToA[11]  = 238; $intToA[12]  = 27;
        $intToA[13]  = 104; $intToA[14]  = 199; $intToA[15]  = 75;  $intToA[16]  = 4;
        $intToA[17]  = 100; $intToA[18]  = 224; $intToA[19]  = 14;  $intToA[20]  = 52;
        $intToA[21]  = 141; $intToA[22]  = 239; $intToA[23]  = 129; $intToA[24]  = 28;
        $intToA[25]  = 193; $intToA[26]  = 105; $intToA[27]  = 248; $intToA[28]  = 200;
        $intToA[29]  = 8;   $intToA[30]  = 76;  $intToA[31]  = 113; $intToA[32]  = 5;
        $intToA[33]  = 138; $intToA[34]  = 101; $intToA[35]  = 47;  $intToA[36]  = 225;
        $intToA[37]  = 36;  $intToA[38]  = 15;  $intToA[39]  = 33;  $intToA[40]  = 53;
        $intToA[41]  = 147; $intToA[42]  = 142; $intToA[43]  = 218; $intToA[44]  = 240;
        $intToA[45]  = 18;  $intToA[46]  = 130; $intToA[47]  = 69;  $intToA[48]  = 29;
        $intToA[49]  = 181; $intToA[50]  = 194; $intToA[51]  = 125; $intToA[52]  = 106;
        $intToA[53]  = 39;  $intToA[54]  = 249; $intToA[55]  = 185; $intToA[56]  = 201;
        $intToA[57]  = 154; $intToA[58]  = 9;   $intToA[59]  = 120; $intToA[60]  = 77;
        $intToA[61]  = 228; $intToA[62]  = 114; $intToA[63]  = 166; $intToA[64]  = 6;
        $intToA[65]  = 191; $intToA[66]  = 139; $intToA[67]  = 98;  $intToA[68]  = 102;
        $intToA[69]  = 221; $intToA[70]  = 48;  $intToA[71]  = 253; $intToA[72]  = 226;
        $intToA[73]  = 152; $intToA[74]  = 37;  $intToA[75]  = 179; $intToA[76]  = 16;
        $intToA[77]  = 145; $intToA[78]  = 34;  $intToA[79]  = 136; $intToA[80]  = 54;
        $intToA[81]  = 208; $intToA[82]  = 148; $intToA[83]  = 206; $intToA[84]  = 143;
        $intToA[85]  = 150; $intToA[86]  = 219; $intToA[87]  = 189; $intToA[88]  = 241;
        $intToA[89]  = 210; $intToA[90]  = 19;  $intToA[91]  = 92;  $intToA[92]  = 131;
        $intToA[93]  = 56;  $intToA[94]  = 70;  $intToA[95]  = 64;  $intToA[96]  = 30;
        $intToA[97]  = 66;  $intToA[98]  = 182; $intToA[99]  = 163; $intToA[100] = 195;
        $intToA[101] = 72;  $intToA[102] = 126; $intToA[103] = 110; $intToA[104] = 107;
        $intToA[105] = 58;  $intToA[106] = 40;  $intToA[107] = 84;  $intToA[108] = 250;
        $intToA[109] = 133; $intToA[110] = 186; $intToA[111] = 61;  $intToA[112] = 202;
        $intToA[113] = 94;  $intToA[114] = 155; $intToA[115] = 159; $intToA[116] = 10;
        $intToA[117] = 21;  $intToA[118] = 121; $intToA[119] = 43;  $intToA[120] = 78;
        $intToA[121] = 212; $intToA[122] = 229; $intToA[123] = 172; $intToA[124] = 115;
        $intToA[125] = 243; $intToA[126] = 167; $intToA[127] = 87;  $intToA[128] = 7;
        $intToA[129] = 112; $intToA[130] = 192; $intToA[131] = 247; $intToA[132] = 140;
        $intToA[133] = 128; $intToA[134] = 99;  $intToA[135] = 13;  $intToA[136] = 103;
        $intToA[137] = 74;  $intToA[138] = 222; $intToA[139] = 237; $intToA[140] = 49;
        $intToA[141] = 197; $intToA[142] = 254; $intToA[143] = 24;  $intToA[144] = 227;
        $intToA[145] = 165; $intToA[146] = 153; $intToA[147] = 119; $intToA[148] = 38;
        $intToA[149] = 184; $intToA[150] = 180; $intToA[151] = 124; $intToA[152] = 17;
        $intToA[153] = 68;  $intToA[154] = 146; $intToA[155] = 217; $intToA[156] = 35;
        $intToA[157] = 32;  $intToA[158] = 137; $intToA[159] = 46;  $intToA[160] = 55;
        $intToA[161] = 63;  $intToA[162] = 209; $intToA[163] = 91;  $intToA[164] = 149;
        $intToA[165] = 188; $intToA[166] = 207; $intToA[167] = 205; $intToA[168] = 144;
        $intToA[169] = 135; $intToA[170] = 151; $intToA[171] = 178; $intToA[172] = 220;
        $intToA[173] = 252; $intToA[174] = 190; $intToA[175] = 97;  $intToA[176] = 242;
        $intToA[177] = 86;  $intToA[178] = 211; $intToA[179] = 171; $intToA[180] = 20;
        $intToA[181] = 42;  $intToA[182] = 93;  $intToA[183] = 158; $intToA[184] = 132;
        $intToA[185] = 60;  $intToA[186] = 57;  $intToA[187] = 83;  $intToA[188] = 71;
        $intToA[189] = 109; $intToA[190] = 65;  $intToA[191] = 162; $intToA[192] = 31;
        $intToA[193] = 45;  $intToA[194] = 67;  $intToA[195] = 216; $intToA[196] = 183;
        $intToA[197] = 123; $intToA[198] = 164; $intToA[199] = 118; $intToA[200] = 196;
        $intToA[201] = 23;  $intToA[202] = 73;  $intToA[203] = 236; $intToA[204] = 127;
        $intToA[205] = 12;  $intToA[206] = 111; $intToA[207] = 246; $intToA[208] = 108;
        $intToA[209] = 161; $intToA[210] = 59;  $intToA[211] = 82;  $intToA[212] = 41;
        $intToA[213] = 157; $intToA[214] = 85;  $intToA[215] = 170; $intToA[216] = 251;
        $intToA[217] = 96;  $intToA[218] = 134; $intToA[219] = 177; $intToA[220] = 187;
        $intToA[221] = 204; $intToA[222] = 62;  $intToA[223] = 90;  $intToA[224] = 203;
        $intToA[225] = 89;  $intToA[226] = 95;  $intToA[227] = 176; $intToA[228] = 156;
        $intToA[229] = 169; $intToA[230] = 160; $intToA[231] = 81;  $intToA[232] = 11;
        $intToA[233] = 245; $intToA[234] = 22;  $intToA[235] = 235; $intToA[236] = 122;
        $intToA[237] = 117; $intToA[238] = 44;  $intToA[239] = 215; $intToA[240] = 79;
        $intToA[241] = 174; $intToA[242] = 213; $intToA[243] = 233; $intToA[244] = 230;
        $intToA[245] = 231; $intToA[246] = 173; $intToA[247] = 232; $intToA[248] = 116;
        $intToA[249] = 214; $intToA[250] = 244; $intToA[251] = 234; $intToA[252] = 168;
        $intToA[253] = 80; $intToA[254] = 88;   $intToA[255] = 175;
        return isset($intToA[$int]) ? $intToA[$int] : null;
    }
}
