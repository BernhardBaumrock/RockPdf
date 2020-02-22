# RockPdf Docs

## Getting the mpdf instance

You can do anything that is possible with Mpdf using the mpdf instance:

```php
$pdf = $modules->get('RockPdf');
$mpdf = $pdf->mpdf;
```

See the mpdf docs: https://mpdf.github.io

## Helper shortcuts

Using mpdf directly can be fine, but this module ships with some helpers/shortcuts that make
working with mpdf and processwire easier. One example is debugging. When using Mpdf
debugging can be tedious because you might get some unwanted results in your pdf and
you don't know what's causing this problem. Getting only the generated HTML is also
not possible by default and you would need to implement some other output strategy like
shown here: https://mpdf.github.io/getting-started/html-or-php.html;

My solution is simple: There are some custom proxy functions that forward your instructions
to Mpdf and log this request as HTML comment. You can then output the generated HTML
**without generating the PDF** (wich is quicker and a lot easier to debug - think
of wrong filepaths for example). You can even use the tracy console to check your code:

```php
$pdf = $modules->get('RockPdf');
$pdf->set('SetHeader', 'This is my header text');
$pdf->write('Hello World ' . date('H:i:s'));
$pdf->write('<!-- my custom comment -->');
d($pdf->html()); // output html in tracy console
d($pdf->save()); // generate pdf
```

Output:

![save.png](images/save.png)
![save.png](images/output.png)

## Different output types

You can use these methods to output your pdf files:

* save() to save your file to the file system
* show() to directly show your file in the browser
* download() to force the browser to download the pdf

## Using fonts

By default MPdf ships with a lot of fonts making the module over 80MB large. I removed almost all of them and you can place the fonts you need in your sites assets folder `/site/assets/RockPdf/fonts`. See https://mpdf.github.io/fonts-languages/fonts-in-mpdf-7-x.html

* Get any TTF font and copy it to `site/assets/RockPdf/fonts`
* Import this font in your code:

```php
// tracy console
$pdf = $modules->get('RockPdf');
$pdf->settings([
  'fontdata' => (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'] + [
    'test' => [
      'R' => 'Garuda.ttf',
      'I' => 'Garuda.ttf',
    ]
  ],
]);
$pdf->write('Hello World ' . date('H:i:s'));
$pdf->write('<p style="font-family: test;">Hello World ' . date('H:i:s') . '</p>');
d($pdf->save());
```

## Using FontAwesome 5 with mPDF

* Download a copy of fontawesome (https://fontawesome.com/download, eg Free for Web)
* Copy the TTF file into your `/site/assets/RockPdf/fonts/` folder
* Add your font to your settings and start using icons in your PDFs

```php
// tracy console
$pdf = $modules->get('RockPdf');
$pdf->settings([
  'fontdata' => (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'] + [
    "far" => [
      'R' => "fa-regular-400.ttf",
      'I' => "fa-regular-400.ttf",
    ],
  ],
]);
$icon = "<i style='font-family: far;'>&#xf118;</i> ";
$pdf->write($icon.'Hello World ' . date('H:i:s'));
d($pdf->save());
```

You'll notice that we used the unicode representation of the icon. You can find
all the codes on the cheatsheet (https://fontawesome.com/cheatsheet) or on the
details page of the icon: https://fontawesome.com/icons/smile?style=regular

Be careful to use the correct style (regular, solid, etc) and unicode!

### Using metadata to get the unicode

Too complicated? RockPdf comes with a helper so that you do not need to take
care of all this and just use the regular fontawesome classes that you might
already be familiar with! To make that work, just copy the icons.json file that
is shipped with fontawesome in the `metadata` folder into the RockPdf assets
folder `/site/assets/RockPdf/fonts`.

```php
// tracy console
$pdf = $modules->get('RockPdf');
$pdf->settings([
  'fontdata' => (new \Mpdf\Config\FontVariables())->getDefaults()['fontdata'] + [
    "far" => [
      'R' => "fa-regular-400.ttf",
      'I' => "fa-regular-400.ttf",
    ],
  ],
]);
$pdf->write("<style>.far { font-family: far; color: blue; }</style>");
$icon = $pdf->icon('far fa-smile');
$pdf->write($icon.'Hello World ' . date('H:i:s'));
d($pdf->html()); // print content to console
$pdf->save(); // save file to file system
```

![img](https://i.imgur.com/UxeTjqe.png)
![img](https://i.imgur.com/U1OQrAz.png)

Using this technique you can easily style your icons using CSS or even LESS
(when using RockLESS). Special thx to 
[jamesfairhurst](https://github.com/mpdf/mpdf/issues/49#issuecomment-259455136)

## Setting a Background (using mpdf features)

Example implementation in a custom module:

```php
/**
 * Add Background PDF
 */
public function addBackground($pdf) {
  $page = $this->pages->get("template=settings");
  $pdfs = $page->getUnformatted('calendarbackground'); // files field
  if(!$pdfs OR !$pdfs->count()) return; // no field or no file
  $pdf->mpdf->SetDocTemplate($pdfs->first()->filename);
}
```

## Page margins

```php
$pdf = $modules->get('RockPdf');
$pdf->settings([
  'margin_top' => 50,
]);
$pdf->write('hello world');
$pdf->save();
```

Or via CSS:

```php
$pdf = $modules->get('RockPdf');
$pdf->write("<style>@page { margin: 0}</style>");
$pdf->write('hello world');
$pdf->save();
```

![img](https://i.imgur.com/nrh263C.png)
