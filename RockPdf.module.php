<?php namespace ProcessWire;

/**
 * mPDF Module for ProcessWire
 * @author Bernhard Baumrock, baumrock.com
 * Licensed under MIT
 */

class RockPdf extends WireData implements Module {

  public $mpdf; // holds the mpdf instance
  private $mpdfinstance;
  private $html;

  /**
   * Initialize the module (optional)
   */
  public function init($options = []) {
    // make sure the assets folder exists
    $this->wire->files->mkdir($this->wire->config->paths->assets . $this->className . '/fonts', true);

    $this->settings($options);
  }

  public function settings($options = []) {
    // merge defaults
    $defaults = [
      'tempDir' => $this->wire->files->tempDir('RockPdf'),
      'fontDir' => [
        __DIR__ . '/vendor/mpdf/mpdf/ttfonts',
        $this->wire->config->paths->assets . $this->className . '/fonts',
      ],
    ];
    $options = array_merge($defaults, $options);

    /* load mpdf library */
    require_once(__DIR__ . '/vendor/autoload.php');
    $this->mpdf = new \Mpdf\Mpdf($options);
  }

  /* ########## proxy to mpdf ########## */

  public function write($data) {
    $this->addHtml($data);
    $this->mpdf->writeHTML($data);
  }

  public function set($key, $value) {
    $this->addHtml([$key, $value]);
    $this->mpdf->{$key}($value);
  }

  /* ########## output ########## */

  /**
   * save output to file
   * @param string filename, filename of the pdf
   */
  public function save($filename = 'output.pdf') {
    // prepare filename
    $filename = $this->getAbsolute($filename); // make sure it is absolute

    // make sure the filename + path are valid
    $info = (object)pathinfo($filename);
    if(!is_dir($info->dirname)) {
      throw new WireException("dir {$info->dirname} in $filename does not exist");
    }
    if(!$info->extension OR $info->extension != 'pdf') {
      throw new WireException("extension must be .pdf");
    }

    // make sure it is a valid filename
    $this->mpdf->Output($filename, \Mpdf\Output\Destination::FILE);
    if(is_file($filename)) {
      $url = str_replace($this->config->paths->root, $this->config->urls->root, $filename);
      return (object)[
        'url' => $url,
        'path' => $filename,
        'httpUrl' => rtrim($this->pages->get(1)->httpUrl, '/') . $url,
      ];
    }
    return false;
  }

  /**
   * show output in browser
   * @param string filename, filename when user saves the pdf
   */
  public function show($filename = 'output.pdf') {
    if($this->isAbsolute($filename)) throw new WireException('filename must not be a path');
    $this->mpdf->Output($filename, \Mpdf\Output\Destination::INLINE);
  }
  
  /**
   * download file
   * @param string filename, filename of the pdf
   */
  public function download($filename = 'output.pdf') {
    if($this->isAbsolute($filename)) throw new WireException('filename must not be a path');
    $this->mpdf->Output($filename, \Mpdf\Output\Destination::DOWNLOAD);
  }

  /**
   * get html markup of the generated pdf
   */
  public function html() {
    return $this->html;
  }

  /* ########## helper functions ########## */

  /**
   * add data to html string
   */
  private function addHTML($data) {
    if($this->html) $this->html .= "\n";

    if(is_array($data)) {
      if(is_array($data[1])) $this->html .= "<!-- \$mpdf->{$data[0]}(" . print_r($data[1], true) . ") -->";
      else $this->html .= "<!-- \$mpdf->{$data[0]}('" . print_r($data[1], true) . "') -->";
    }
    else $this->html .= $data;
  }

  /**
   * check if the filename is an absolute path
   */
  public function isAbsolute($filename) {
    return (strpos($filename, '/') === 0 || strpos($filename, ':/') !== false);
  }

  /**
   * return absolute filepath
   */
  public function getAbsolute($filename) {
    if($this->isAbsolute($filename)) return $filename;
    return $this->config->paths->assets . 'RockPdf/' . $filename;
  }

  /**
   * Return debug info array
   * @return array
   */
  public function __debugInfo() {
    return [
      'mpdf' => $this->mpdf,
      'html' => $this->html,
    ];
  }
}