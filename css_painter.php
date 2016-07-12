<?php
/**
 * @file   : css_painter.php
 * @author : aesbovis
 * @e-mail : aesbovis@gmail.com
 * @version: 1.0
 * @lisence: GPLv3
 * @date   : 2016-07
 * @Description:
 * The css painter will analyse the image file
 * and draw it on the web page with only one div by CSS
 * using the property box-shadow.
 */

/**
 * @class  : css_painter
 * @author : aesbovis
 */
class css_painter {
	/**
	 * Block Size.
	 * Default (3).
	 * Input the block size when you create a new object.
	 * If the size is 3, the painter will average the colors of every 3*3 block,
	 * and redraw the picture with a 3*3 single color block.
	 * @var integer
	 */
	private $pixel_block_size;
	/**
	* Block colors.
  * Structure: array[X-axis][Y-axis] = hexadecimal color code
  * @var string
  */
	private $pixel_block_colors = array();
	/**
	* Block X-axis.
  * @var integer
  */
	private $pixel_block_X;
	/**
	* Block Y-axis.
  * @var integer
  */
	private $pixel_block_Y;

	/**
	* Original image source.
  * Ranges: null ( default ), png, jpeg, bmp.
	* When null, it is an invalid image style.
  * @var string
  */
	private $image;
	/**
	 * Image path.
	 * Input the image path when you create a new object.
	 * @var string
	 */
	private $image_path;
	/**
	 * Image path.
	 * @var string
	 */
	private $image_name;
	/**
	* Original image style.
  * Ranges: null ( default ), png, jpeg, bmp.
	* When null, it is an invalid image style.
  * @var string
  */
	private $image_style = null;
	/**
	* Original image width.
  * Ranges: 1~500.
	* When > 500, it will be compress to less than 500
  * @var integer
  */
	private $image_width;
	/**
	* Original image height.
  * Ranges: 1~500.
	* When > 500, it will be compress to less than 500
  * @var integer
  */
	private $image_height;
	/**
	 * The CSS file path.
	 * @var string
	 */
	private $css_file_path;
	/**
	 * The HTML file path.
	 * @var string
	 */
	private $html_file_path;


	/*
	 * @access public
	 * @param string $img_path The original image path.
	 * @param integer $block_size The size of the pixel block.
	 */
	public function __construct( $img_path, $block_size = 3 ) {
		$this->image_path = $img_path;
		$this->pixel_block_size = $block_size;

		$this->_image_detecter();
		$this->css_file_path = $this->image_name . '.css';
		$this->html_file_path = $this->image_name . '.html';
		$this->_css_sampler();

		$this->_write_css_file();
		$this->_write_html_file();
		header( 'Location: ' . $this->html_file_path );
	}
	public function __destruct() {
		imagedestroy($this->image);
	}

	/**
	 * @access protected
	 */
	protected function _image_detecter() {
		$path_array = explode( '/', $this->image_path );
		$path_count = count( $path_array ) - 1;
		$name_array = explode( '.', $path_array[$path_count] );
		$name_count = count( $name_array ) - 1;
		$this->image_name = $name_array[0];
		if( 'png' == $name_array[$name_count] || 'PNG' == $name_array[$name_count] ) {
			$this->image_style = 'png';
		} elseif ( 'bmp' == $name_array[$name_count] || 'BMP' == $name_array[$name_count] ) {
			$this->image_style = 'bmp';
		} elseif ( 'jpg' == $name_array[$name_count] || 'JPG' == $name_array[$name_count] || 'jpeg' == $name_array[$name_count] || 'JPEG' == $name_array[$name_count] ) {
			$this->image_style = 'jpeg';
		}

		if( $this->image_style ) {
			$imgcreate = 'imagecreatefrom' . $this->image_style;
			$image = $imgcreate( $this->image_path );
			$width = imagesX($image);
			$height = imagesY($image);
			if( 500 >= $width && 500 >= $height ) {
				$this->image = $image;
				$this->image_width = $width;
				$this->image_height = $height;
			} else {
				if( $width > $height ) {
					$this->image_width = 500;
					$this->image_height = 500 * $height / $width;
					$this->image_height = ( int )$this->image_height;
				} elseif ( $width == $height ) {
					$this->image_width = 500;
					$this->image_height = 500;
				} else {
					$this->image_height = 500;
					$this->image_width = 500 * $width / $height;
					$this->image_width = ( int )$this->image_width;
				}
				$this->image = imagecreatetruecolor( $this->image_width, $this->image_height );
				imagealphablending( $this->image, false );
				imagesavealpha( $this->image, true );
				imagecopyresampled( $this->image, $image, 0, 0, 0, 0, $this->image_width, $this->image_height, $width, $height );
			}
			imagedestroy($image);
		} else {
			echo 'A wrong path: the image will be only PNG or MBP or JPEG style!';
		}
	}
	/**
	 * @access protected
	 */
	protected function _css_sampler() {
		$this->pixel_block_X = ceil( $this->image_width / $this->pixel_block_size );
		$this->pixel_block_Y = ceil( $this->image_height / $this->pixel_block_size );

		for( $i = 0; $i < $this->pixel_block_X; $i++ ) {
			for( $j = 0; $j < $this->pixel_block_Y; $j++ ) {
				$block_array = array( 'r' => 0, 'g' => 0, 'b' => 0);
				$k = 0;

				for( $m = 0; $m < $this->pixel_block_size; $m++ ) {
					for( $n = 0; $n < $this->pixel_block_size; $n++ ) {

						$w = $i * $this->pixel_block_size + $m;
						$h = $j * $this->pixel_block_size + $n;
						if( $this->image_width > $w && $this->image_height > $h) {
							$pixel_color = imagecolorat($this->image, $w, $h);
							$r = ($pixel_color >> 16) & 0xFF;
							$g = ($pixel_color >> 8) & 0xFF;
							$b = $pixel_color & 0xFF;
							$block_array['r'] = $block_array['r'] + $r;
							$block_array['g'] = $block_array['g'] + $g;
							$block_array['b'] = $block_array['b'] + $b;
							$k++;
						}

					}
				}

				$x = round( $block_array['r'] / $k );
				$r = dechex( $x );
				if( $x < 16 ) {
					$r = '0' . $r;
				}

				$y = round( $block_array['g'] / $k );
				$g = dechex( $y );
				if( $y < 16 ) {
					$g = '0' . $g;
				}

				$z = round( $block_array['b'] / $k );
				$b = dechex( $z );
				if( $z < 16 ) {
					$b = '0' . $b;
				}
				$this->pixel_block_colors[$i][$j] = $r . $g . $b;
			}
		}
	}
	/**
	 * @access protected
	 */
	protected function _write_css_file() {
		$css_content = 'body {' . "\n"
			.'margin: 0;' . "\n"
			.'padding: 0;' . "\n"
			.'}' . "\n"
			.'h1 {' . "\n"
			.'text-align: center;' . "\n"
			.'}' . "\n"
			.'#frame {' . "\n"
			.'width: ' . $this->image_width . 'px;' . "\n"
			.'height: ' . $this->image_height . 'px;' . "\n"
			.'margin: 100px auto;' . "\n"
			.'border-color: #bbb #aaa #999 #aaa;' . "\n"
			.'border-style: solid;' . "\n"
			.'border-width: 10px;' . "\n"
			.'}' . "\n"
			.'#' . $this->image_name . ' {'."\n"
			.'width: ' . $this->pixel_block_size . 'px;' . "\n"
			.'height: ' . $this->pixel_block_size . 'px;' . "\n"
			.'background: #' . $this->pixel_block_colors[0][0] . ';' . "\n"
			.'box-shadow:';
		for( $i = 0; $i < $this->pixel_block_X; $i++ ) {
			for( $j = 0; $j < $this->pixel_block_Y; $j++ ) {
				$w = $i * $this->pixel_block_size;
				$h = $j * $this->pixel_block_size;
				if( 0 == $i && 0== $j ) {
					$css_content .= "\n" . $w . 'px ' . $h . 'px 0 #' . $this->pixel_block_colors[$i][$j];
				} else {
					$css_content .= ",\n" . $w . 'px ' . $h . 'px 0 #' . $this->pixel_block_colors[$i][$j];
				}
			}
		}
		$css_content .= ";\n}";
		$fp = fopen( $this->css_file_path, 'w');
		fwrite( $fp, $css_content );
		fclose( $fp );
	}
	/**
	 * @access protected
	 */
	protected function _write_html_file() {
		$html_content =  '<!DOCTYPE html>' . "\n"
			.'<html lang="en">' . "\n"
			.'<head>' . "\n"
			.'<meta charset="UTF-8">' . "\n"
			.'<title>CSS Pixel Painting</title>' . "\n"
			.'<link rel="stylesheet" href="' . $this->css_file_path . '" />' . "\n"
			.'</head>' . "\n"
			.'<body>' . "\n"
			.'<h1>CSS pixel painting</h1>' . "\n"
			.'<div id="frame">' . "\n"
			.'<div id="' . $this->image_name . '"></div>' . "\n"
			.'</div>' . "\n"
			.'</body>' . "\n"
			.'</html>';
		$fp = fopen( $this->html_file_path, 'w');
		fwrite( $fp, $html_content );
		fclose( $fp );
	}
}

$img_path = ''; // image path
$one_div_css_painter = new css_painter( $img_path );
?>
