<?php namespace SimpleSoftwareIO\QrCode;
/**
 * Simple Laravel QrCode Generator
 * A simple wrapper for the popular BaconQrCode made for Laravel.
 *
 * @link http://www.simplesoftware.io
 * @author SimpleSoftware support@simplesoftware.io
 *
 */

use Illuminate\Support\ServiceProvider;
use BaconQrCode;
use BaconQrCode\Writer;
use BaconQrCode\Renderer\Image\RendererInterface;
use BaconQrCode\Renderer\Image\Svg;
use BaconQrCode\Renderer\Image\Eps;
use BaconQrCode\Renderer\Image\Png;
use BaconQrCode\Renderer\Color\Rgb;
use BaconQrCode\Encoder\Encoder;
use BaconQrCode\Common\ErrorCorrectionLevel;

class BaconQrCodeGenerator implements QrCodeInterface {

    /**
     * Holds the BaconQrCode Writer Object
     *
     * @var \BaconQrCode\Writer
     */
    protected $writer;

    /**
     * Holds the QrCode error correction levels.  This is stored by using the BaconQrCode ErrorCorrectionLevel class constants.
     *
     * @var \BaconQrCode\Common\ErrorCorrectionLevel
     */
    protected $errorCorrection = ErrorCorrectionLevel::L;

    /**
     * Holds the Encoder mode to encode a QrCode.
     *
     * @var string
     *
     */
    protected $encoding = Encoder::DEFAULT_BYTE_MODE_ECODING;

    /**
     * Holds an image string that will be merged with the QrCode.
     *
     * @var null|string
     */
    protected $imageMerge = null;

    /**
     * The percentage that a merged image should take over the source image.
     *
     * @var float
     */
    protected $imagePercentage = .2;

    /**
     * Whether the quality of the merge has to of high quality (resampled)
     *
     * @var bool
     */
    protected $highQuality = false;

    /**
     * Creates a new QrCodeGenerator with a Writer class and with a SVG renderer set as the default.
     */
    public function __construct(Writer $writer = null, RendererInterface $format = null)
    {
        $format = $format ?: new Svg;
        $this->writer = $writer ?: new Writer($format);
    }

    /**
     * Generates a QrCode
     *
     * @param string $text The text to be converted into a QrCode
     * @param null|string $filename The filename and path to save the QrCode file
     * @return string|void Returns a QrCode string depending on the format, or saves to a file.
     */
    public function generate($text, $filename = null)
    {
        $qrCode = $this->writer->writeString($text, $this->encoding, $this->errorCorrection);

        if ($this->imageMerge !== null)
        {
            $merger = new ImageMerge(new Image($qrCode), new Image($this->imageMerge), $this->highQuality);
            $qrCode = $merger->merge($this->imagePercentage);
        }

        if ($filename === null)
        {
            return $qrCode;
        }
        else
        {
            file_put_contents($filename, $qrCode);
        }
    }

    /**
     * Merges an image with the center of the QrCode
     *
     * @param $filepath string The filepath to an image
     * @param $percentage float The amount that the merged image should be placed over the qrcode.
     * @param $absolute boolean Whether to use an absolute filepath or not.
     * @param $highQuality boolean Whether we want a high merge quality (resampled vs just resized).
     * @return $this
     */
    public function merge($filepath, $percentage = .2, $absolute = false, $highQuality = false)
    {
        if (function_exists('base_path') && ! $absolute)
        {
            $filepath = base_path() . $filepath;
        }

        if( ! is_null($highQuality))
        {
            $this->highQuality = true && $highQuality;
        }

        $this->imageMerge = file_get_contents($filepath);
        $this->imagePercentage = $percentage;

        return $this;
    }

    /**
     * Merges an image string with the center of the QrCode, does not check for correct format
     *
     * @param $content string The string contents of an image.
     * @param $percentage float The amount that the merged image should be placed over the qrcode.
     * @param $highQuality boolean Whether we want a high merge quality (resampled vs just resized).
     * @return $this
     */
    public function mergeString($content, $percentage = .2, $highQuality = null)
    {
        $this->imageMerge = $content;
        $this->imagePercentage = $percentage;

        if(!is_null($highQuality))
        {
            $this->highQuality = true && $highQuality;
        }

        return $this;
    }

    /**
     * Sets the quality of the merge, when set to true the merge will use a resampled merge, when set to false will set
     * the merge to a simple resized merge. The resampled merge will yield a higher quality due to anti-aliassing, but
     * takes more CPU time to compute
     *
     * @param $highQuality boolean Whether the quality should be high (true) or low (false)
     * @return $this
     */
    public function setMergeQuality($highQuality){
        $this->highQuality = true && $highQuality;

        return $this;
    }

    /**
     * Switches the format of the outputted QrCode or defaults to SVG
     *
     * @param string $format The desired format.
     * @return $this
     * @throws \InvalidArgumentException
     */
    public function format($format)
    {
        switch($format)
        {
            case 'png':
                $this->writer->setRenderer(new Png);
                break;
            case 'eps':
                $this->writer->setRenderer(new Eps);
                break;
            case 'svg':
                $this->writer->setRenderer(new Svg);
                break;
            default:
                throw new \InvalidArgumentException('Invalid format provided.');
        }
        return $this;
    }

    /**
     * Changes the size of the QrCode
     *
     * @param int $pixels The size of the QrCode in pixels
     * @return $this
     */
    public function size($pixels)
    {
        $this->writer->getRenderer()->setHeight($pixels);
        $this->writer->getRenderer()->setWidth($pixels);
        return $this;
    }

    /**
     * Changes the foreground color of a QrCode
     *
     * @param int $red
     * @param int $green
     * @param int $blue
     * @return $this
     */
    public function color($red, $green, $blue)
    {
        $this->writer->getRenderer()->setForegroundColor(new Rgb($red, $green, $blue));
        return $this;
    }

    /**
     * Changes the background color of a QrCode
     *
     * @param int $red
     * @param int $green
     * @param int $blue
     * @return $this
     */
    public function backgroundColor($red, $green, $blue)
    {
        $this->writer->getRenderer()->setBackgroundColor(new Rgb($red, $green, $blue));
        return $this;
    }

    /**
     * Changes the error correction level of a QrCode
     *
     * @param string $level Desired error correction level.  L = 7% M = 15% Q = 25% H = 30%
     * @return $this
     */
    public function errorCorrection($level)
    {
        $this->errorCorrection = constant("BaconQrCode\Common\ErrorCorrectionLevel::$level");
        return $this;
    }

    /**
     * Creates a margin around the QrCode
     *
     * @param int $margin The desired margin in pixels around the QrCode
     * @return $this
     */
    public function margin($margin)
    {
        $this->writer->getRenderer()->setMargin($margin);
        return $this;
    }

    /**
     * Sets the Encoding mode.
     *
     * @param string $encoding
     * @return $this
     */
    public function encoding($encoding)
    {
        $this->encoding = $encoding;
        return $this;
    }

    /**
     * Creates a new datatype object and then generates a QrCode.
     *
     * @param $method
     * @param $arguments
     * @return void
     */
    public function __call($method, $arguments)
    {
        $dataType = $this->createClass($method);

        $dataType->create($arguments);

        return $this->generate(strval($dataType));
    }

    /**
     * Creates a new DataType class dynamically.
     *
     * @param string $method
     * @return SimpleSoftwareIO\QrCode\DataTypes\DataTypeInterface
     */
    private function createClass($method)
    {
        $class = $this->formatClass($method);

        if ( ! class_exists($class)) throw new \BadMethodCallException;

        return new $class;
    }

    /**
     * Formats the method name correctly.
     *
     * @param $method
     * @return string
     */
    private function formatClass($method)
    {
        $method = ucfirst($method);

        $class = "SimpleSoftwareIO\QrCode\DataTypes\\" . $method;
        return $class;
    }
}
