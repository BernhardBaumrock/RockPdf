<?php namespace ProcessWire;

/**
 * mPDF Module for ProcessWire
 * @author Bernhard Baumrock, baumrock.com
 * Licensed under MIT
 */

class RockPdf extends WireData implements Module {

  /** @var \Mpdf\Mpdf */
  public $mpdf;

  private $html;

  // icon metadata
  private $icons;

  public static function getModuleInfo() {
    return [
      'title' => 'RockPdf',
      'version' => '2.1.0',
      'summary' => 'mPDF helper module for ProcessWire CMS.',
      'singular' => false,
      'autoload' => false,
      'icon' => 'file-pdf-o',
    ];
  }

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

  /**
   * Dump this pdf as iframe to tracy
   * @return void
   */
  public function dump() {
    $src = $this->save()->url;
    echo "<iframe src='$src' style='width: 100%; height: 300px;'></iframe>";
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
   * Wrap string in a table for PDF formatting
   * mPdf does not support block level elements in table cells
   * The workaround is to do everything table-based like in the 90s :)
   */
  public function div($str, $class = null) {
    return "<div class='$class'>$str</div>\n";
  }

  /**
   * return absolute filepath
   */
  public function getAbsolute($filename) {
    if($this->isAbsolute($filename)) return $filename;
    return $this->config->paths->assets . 'RockPdf/' . $filename;
  }

  /**
   * Get icon metadata
   * @return
   */
  public function getIconData() {
    if($this->icons) return $this->icons;

    // get data from json
    $path = $this->config->paths->assets . "RockPdf/";
    $file = $path."icons.json";
    if(!is_file($file)) throw new WireException("icons.json not found in $path");

    $json = json_decode(file_get_contents($file));
    if(!$json) throw new WireException("Unable to read icons.json file in $path");

    $this->icons = $json;
    return $json;
  }

  /**
   * Get fontawesome icon based on class
   * @return string
   */
  public function icon($class) {
    // get metadata
    $data = $this->getIconData();

    // get portion of class that defines the icon
    $icon = null;
    foreach(explode(" ", $class) as $c) {
      if($icon) continue; // skip if already found
      if(strpos($c, "fa-") === 0) $icon = $c;
    }
    if(!$icon) throw new WireException("No icon class defined (fa-...)");
    $icon = substr($icon, 3);

    // get unicode
    $code = $data->$icon->unicode;
    return "<i class='$class'>&#x$code;</i>";
  }

  /**
   * check if the filename is an absolute path
   */
  public function isAbsolute($filename) {
    return (strpos($filename, '/') === 0 || strpos($filename, ':/') !== false);
  }

  /**
   * Wrap string in a table for PDF formatting
   * mPdf does not support block level elements in table cells
   * The workaround is to do everything table-based like in the 90s :)
   */
  public function td($str, $tdClass = null, $tableClass = null) {
    return "<table class='$tableClass'><tr><td class='$tdClass'>$str</td></tr></table>\n";
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
